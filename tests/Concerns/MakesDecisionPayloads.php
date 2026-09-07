<?php

namespace Tests\Concerns;

use App\Modules\Job\Model\Job;
use App\Modules\Job\Service\JobDecisionService;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Builds the object the runner actually writes for a lambdaselect job.
 *
 * Not a simplified stand-in: the shape below was read off the live MinIO object
 * for job 34226 on 2026-09-07. It matters that the tests use the real one --
 * the decision body is a JSON STRING inside an envelope inside a zip entry
 * whose name has to contain "output", and every one of those three layers is a
 * place a reader can go wrong while still returning something plausible.
 *
 * Storage::fake('s3runner') everywhere; nothing here touches the real store.
 */
trait MakesDecisionPayloads
{
    /**
     * Give $job a lambda-jobdata report row and put the object behind it.
     *
     * @param array<string, mixed>|null $payload  the decision function's response, or null for an empty zip
     * @param array<string, mixed>      $envelope extra runner-envelope fields
     */
    protected function storeDecisionFor(Job $job, ?array $payload, array $envelope = []): Report
    {
        $key = 'reports/'.JobDecisionService::JOBDATA_MARKER.'-lambdaselectjob-youtube_video_select-'.$job->id;

        $report = Report::factory()->create([
            'job_id' => $job->id,
            'user_id' => $job->user_id,
            'filename' => basename($key),
            'path' => Report::S3PREFIX.$key,
            'mimetype' => Report::S3MIMETYPE,
            'filecontent' => null,
        ]);

        Storage::disk(Report::S3DISK)->put($key, $this->decisionZip($job, $payload, $envelope));

        return $report;
    }

    /**
     * A report row pointing at an object that is not there.
     */
    protected function storeMissingDecisionFor(Job $job): Report
    {
        $key = 'reports/'.JobDecisionService::JOBDATA_MARKER.'-lambdaselectjob-youtube_video_select-'.$job->id;

        return Report::factory()->create([
            'job_id' => $job->id,
            'user_id' => $job->user_id,
            'filename' => basename($key),
            'path' => Report::S3PREFIX.$key,
            'mimetype' => Report::S3MIMETYPE,
            'filecontent' => null,
        ]);
    }

    /**
     * The zip the runner writes: one input entry and one output entry.
     *
     * The input entry is present and deliberately junk -- on a real object it
     * is a several-MB page dump, and a reader that opens it instead of the
     * output entry must fail loudly rather than quietly parse the wrong thing.
     *
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed>      $envelope
     */
    protected function decisionZip(Job $job, ?array $payload, array $envelope = []): string
    {
        $entries = ['20260907-153436_lambda_input_jobdata_'.$job->id.'.json' => '{"job":{"id":'.$job->id.'}}'];

        if (null !== $payload) {
            $entries['20260907-153436_lambda_output_jobdata_'.$job->id.'.json'] = (string) json_encode(array_merge([
                'statusCode' => 200,
                'success' => true,
                'functionError' => null,
                'payload' => json_encode($payload),
            ], $envelope));
        }

        return $this->zipFrom($entries);
    }

    /**
     * A payload from the decision function currently deployed.
     *
     * @param  array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function reportedPayload(array $overrides = []): array
    {
        return array_merge([
            'xpath' => '(//ytd-compact-video-renderer)[3]',
            'selected' => ['id' => 'abc123', 'title' => 'A video'],
            'degraded' => false,
            'treatment_lost' => false,
            'selection_mode' => 'random',
            'filters' => [
                'already_watched' => 'not_implemented',
                'mainstream_media' => 'skipped',
                'children_entertainment' => 'ok',
                'similar' => 'skipped',
                'political' => 'skipped',
                'fringe' => 'skipped',
            ],
            'llm' => ['available' => true, 'error_type' => null, 'error' => null],
            'warnings' => [],
        ], $overrides);
    }

    /**
     * A payload from the decision function that was deployed before the
     * degradation report existed. Its key set is exactly this, and the archive
     * is full of them.
     *
     * @return array<string, mixed>
     */
    protected function legacyPayload(): array
    {
        return [
            'xpath' => '(//ytd-compact-video-renderer)[2]',
            'present' => ['id' => 'zzz'],
            'procedure' => [],
            'selected' => ['id' => 'yyy'],
            'timestamps' => ['start' => 1],
            'warnings' => [],
        ];
    }

    /**
     * @param array<string, string> $entries
     */
    protected function zipFrom(array $entries): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'pm-test-zip-');
        // ZipArchive refuses to create over an existing empty file.
        @unlink($path);

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        $bytes = (string) file_get_contents($path);
        @unlink($path);

        return $bytes;
    }
}
