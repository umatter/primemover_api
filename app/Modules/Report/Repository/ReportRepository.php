<?php

namespace App\Modules\Report\Repository;

use App\Facades\Factories\PipelineFactory;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Facade\Service\ReportService;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Pipe\AddFilesToArchive;
use App\Modules\Report\Pipe\AssertJobHasReports;
use App\Modules\Report\Pipe\CreateJobReportBinaries;
use App\Modules\Report\Pipe\JobReportData;
use App\Modules\Report\Pipe\TakeOnlyReportsWithPathOrFail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportRepository
{
    public function __construct(protected Report $report) {}

    public function all(): LengthAwarePaginator
    {
        return Report::queryBuilder()->visibleTo()->paginate(10);
    }

    public function save(array $attributes): Report
    {
        return Report::create($attributes);
    }

    public function delete(Report $report): bool
    {
        return $report->delete();
    }

    /**
     * One stored object, streamed off the runner's object store.
     *
     * null means "there is nothing to serve" -- the row carries no path, or the
     * store has no object at it -- and the controller turns that into a 404.
     *
     * This method did not exist until 2026-09-07. ReportController::getFile()
     * called it anyway, so GET /v1/file/{report} answered 500 on every request
     * it has ever received: only the whole-job ZIP download worked, and there
     * was no way to fetch a single object. That is what made the decision
     * payload unreachable from the GUI even though the reports row pointing at
     * it was right there in the response.
     *
     * Streamed rather than read into a string: these objects are HARs and page
     * dumps, routinely megabytes, and one of them is a 3 MB decision input.
     */
    public function getFile(Report $report): ?StreamedResponse
    {
        if (blank($report->path)) {
            return null;
        }

        $disk = Storage::disk(Report::S3DISK);
        $key = ReportService::getS3Path($report);

        try {
            if ( ! $disk->exists($key)) {
                return null;
            }
        } catch (Throwable $exception) {
            // An unreachable store is missing evidence about a job, never
            // evidence that the job produced nothing -- so this is logged and
            // rethrown as a 5xx rather than quietly answered 404.
            Log::warning('1757260000: report object store unreachable', [
                'report' => $report->id,
                'disk' => Report::S3DISK,
                'key' => $key,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        // download() rather than response() plus a hand-built header: it is
        // response() with disposition 'attachment', and it runs the filename
        // through Symfony's makeDisposition, which escapes it and adds an ASCII
        // fallback. The filename comes from a runner-written row, so building
        // that header by string concatenation is a header-injection waiting to
        // happen and loses the RFC 5987 encoding for nothing.
        return $disk->download($key, $this->downloadNameFor($report), [
            'Content-Type' => $this->contentTypeFor($report),
        ]);
    }

    public function download(Job $job): ?string
    {
        $jobReportData = JobReportData::from([])->setJob($job);

        return PipelineFactory::create()
            ->send($jobReportData)
            ->through([
                AssertJobHasReports::class,
                TakeOnlyReportsWithPathOrFail::class,
                CreateJobReportBinaries::class,
                AddFilesToArchive::class,
            ])
            ->onFailure(function (JobReportData $jobReportData, Throwable $exception): void {
                Log::error('1698493053: Failed to download report', [
                    'message' => $exception->getMessage(),
                    'job' => $jobReportData->getJob()->id,
                ]);

                throw $exception;
            })
            ->then(fn (JobReportData $jobReportData) => $jobReportData->getZipPath());
    }

    private function downloadNameFor(Report $report): string
    {
        $filename = trim((string) $report->filename);

        return '' === $filename ? 'report-'.$report->id : basename($filename);
    }

    /**
     * The stored mimetype, unless it is the internal pointer marker.
     */
    private function contentTypeFor(Report $report): string
    {
        $mimetype = trim((string) $report->mimetype);

        if ('' === $mimetype || Report::S3MIMETYPE === $mimetype) {
            return 'application/octet-stream';
        }

        return $mimetype;
    }
}
