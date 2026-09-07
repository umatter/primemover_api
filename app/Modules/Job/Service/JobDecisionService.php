<?php

namespace App\Modules\Job\Service;

use App\Modules\Job\Exception\DecisionPayloadException;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Facade\Service\ReportService;
use App\Modules\Report\Model\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * Reads a job's decision payload out of the object store.
 *
 * The runner writes one object per lambdaselect job:
 *
 *     reports/lambda-jobdata-<jobtype>-<task>-<jobId>
 *
 * a zip holding the lambda input and output JSON. 54,000 of them sit in MinIO
 * and, until GET /jobs/{id}/decision, nothing in the API could read one -- the
 * reports table holds only a pointer row (mimetype primemover/s3, filecontent
 * NULL) and the bytes are in the store. That is what makes the HISTORICAL
 * verdicts readable without a backfill: the four decision_* columns only ever
 * describe jobs run after the runner deploy, while the objects go back to the
 * start.
 *
 * The layout is flat -- there is no prefix that selects one job -- so the job's
 * own reports rows are the index into it.
 */
class JobDecisionService
{
    /**
     * The marker in a report's filename/path that makes it a decision payload.
     *
     * The runner's other objects for the same job are static-jobdata (the HAR),
     * static-captcha and page-finalsource; only this one carries the decision.
     */
    public const JOBDATA_MARKER = 'lambda-jobdata';

    /**
     * The zip entry holding the decision function's response.
     *
     * Entries are named "<ts>_lambda_input_jobdata_<jobId>.json" and
     * "<ts>_lambda_output_jobdata_<jobId>.json"; the input is the several-MB
     * page dump the runner sent and is never read here.
     */
    public const OUTPUT_ENTRY_MARKER = 'output';

    /**
     * The report row pointing at this job's decision payload, newest first.
     *
     * Newest because a retried job can have written more than one, and the last
     * decision is the one that produced the data.
     */
    public function reportFor(Job $job): ?Report
    {
        return $job->reports()
            ->whereNotNull('path')
            ->where(fn (Builder $query) => $query
                ->where('filename', 'like', '%'.self::JOBDATA_MARKER.'%')
                ->orWhere('path', 'like', '%'.self::JOBDATA_MARKER.'%'))
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Narrow a job query to jobs that have a decision payload recorded.
     *
     * Used by decisions:backfill to walk the ~54,000 objects without listing
     * the bucket: the reports table is the index. Deliberately not an Eloquent
     * scope -- it belongs with the rest of the knowledge about how a decision
     * object is named, not on the Job model.
     */
    public function whereHasJobData(Builder $jobs): Builder
    {
        return $jobs->whereHas('reports', fn (Builder $reports) => $reports
            ->whereNotNull('path')
            ->where(fn (Builder $query) => $query
                ->where('filename', 'like', '%'.self::JOBDATA_MARKER.'%')
                ->orWhere('path', 'like', '%'.self::JOBDATA_MARKER.'%')));
    }

    /**
     * This job's decision payload, or null when none was ever recorded.
     *
     * null and an exception are different answers and the caller must keep them
     * apart: null is "no decision payload exists for this job" (most jobs never
     * call the decision function), while a DecisionPayloadException is "one
     * exists and the store would not give it to us". The second is missing
     * evidence, never evidence that the job was fine.
     *
     * @throws DecisionPayloadException
     */
    public function payloadFor(Job $job): ?DecisionPayload
    {
        $report = $this->reportFor($job);

        return null === $report ? null : $this->payloadForReport($report);
    }

    /**
     * @throws DecisionPayloadException
     */
    public function payloadForReport(Report $report): DecisionPayload
    {
        $key = ReportService::getS3Path($report);

        try {
            $bytes = Storage::disk(Report::S3DISK)->get($key);
        } catch (Throwable $exception) {
            throw new DecisionPayloadException(
                "the object store did not answer for {$key}: ".$exception->getMessage(),
                previous: $exception
            );
        }

        if (null === $bytes || '' === $bytes) {
            throw new DecisionPayloadException("the object store holds nothing at {$key}");
        }

        [$entry, $envelope] = $this->decodeOutput($bytes, $key);

        return new DecisionPayload(
            report: $report,
            entry: $entry,
            payload: $this->unwrapInvocation($envelope),
            invocation: array_diff_key($envelope, ['payload' => null]),
        );
    }

    /**
     * The four decision_* columns for a payload, or [] when it says nothing.
     *
     * An empty array is the whole point: a payload with no degradation report
     * came from a decision function deployed before the report existed, and
     * writing false into the columns for it would assert that a treatment
     * applied when nobody measured whether it did. Unknown stays NULL.
     *
     * @return array<string, mixed>
     */
    public function columnsFor(DecisionVerdict $verdict): array
    {
        if ( ! $verdict->reported) {
            return [];
        }

        return [
            'decision_degraded' => $verdict->degraded,
            'decision_treatment_lost' => $verdict->treatmentLost,
            'decision_selection_mode' => '' === $verdict->selectionMode ? null : $verdict->selectionMode,
            'decision_report' => [] === $verdict->report ? null : $verdict->report,
        ];
    }

    /**
     * The decision function's own response, unwrapped from the runner envelope.
     *
     * A body that is already bare is returned unchanged, so an object written
     * by a different producer still parses.
     *
     * @param  array<string, mixed> $envelope
     * @return array<string, mixed>
     */
    public function unwrapInvocation(array $envelope): array
    {
        if ( ! array_key_exists('payload', $envelope)) {
            return $envelope;
        }

        $inner = $envelope['payload'];

        if (is_string($inner)) {
            $inner = json_decode($inner, true);
        }

        return is_array($inner) ? $inner : $envelope;
    }

    /**
     * The output JSON out of the stored object.
     *
     * The object is a zip today. A bare JSON body is accepted as well rather
     * than assumed impossible -- the writer has changed shape before, and
     * failing to read a payload that is right there is a worse outcome than a
     * branch that never fires.
     *
     * @return array{0: string, 1: array<string, mixed>}
     *
     * @throws DecisionPayloadException
     */
    private function decodeOutput(string $bytes, string $key): array
    {
        $entry = $this->readOutputEntry($bytes, $key);

        if (null === $entry) {
            $bare = json_decode($bytes, true);

            if (is_array($bare)) {
                return ['', $bare];
            }

            throw new DecisionPayloadException(
                "the object at {$key} is neither a zip holding a lambda output nor JSON"
            );
        }

        [$name, $contents] = $entry;
        $decoded = json_decode($contents, true);

        if ( ! is_array($decoded)) {
            throw new DecisionPayloadException(
                "the lambda output {$name} in {$key} is not valid JSON: ".json_last_error_msg()
            );
        }

        return [$name, $decoded];
    }

    /**
     * The "*output*.json" entry of the zip, or null when it is not a zip.
     *
     * ZipArchive only opens a path, so the bytes go through a temp file. Only
     * the one entry is read: the sibling input entry is the several-MB page
     * dump the runner sent to the decision function and nothing here wants it.
     *
     * @return array{0: string, 1: string}|null
     *
     * @throws DecisionPayloadException
     */
    private function readOutputEntry(string $bytes, string $key): ?array
    {
        $temporary = tempnam(sys_get_temp_dir(), 'pm-decision-');

        if (false === $temporary) {
            throw new DecisionPayloadException('could not create a temporary file to unpack '.$key);
        }

        $zip = new ZipArchive();

        try {
            file_put_contents($temporary, $bytes);

            if (true !== $zip->open($temporary)) {
                return null;
            }

            try {
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $name = (string) $zip->getNameIndex($index);

                    if ( ! str_contains(mb_strtolower($name), self::OUTPUT_ENTRY_MARKER)
                        || ! str_ends_with(mb_strtolower($name), '.json')) {
                        continue;
                    }

                    $contents = $zip->getFromIndex($index);

                    if (false === $contents) {
                        throw new DecisionPayloadException("the entry {$name} in {$key} could not be read");
                    }

                    return [$name, $contents];
                }
            } finally {
                $zip->close();
            }

            throw new DecisionPayloadException("the zip at {$key} holds no lambda output entry");
        } finally {
            @unlink($temporary);
        }
    }
}
