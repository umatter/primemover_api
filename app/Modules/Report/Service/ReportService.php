<?php

namespace App\Modules\Report\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Exception\ReportDownloadException;
use App\Modules\Report\Model\Report;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportService
{
    /**
     * @throws \App\Modules\Report\Exception\ReportDownloadException
     */
    public function getFileByPath(Report $report): ?string
    {
        // The job ZIP pipeline authorises the JOB and then walks that job's
        // report rows, so it inherits the same decoupling the single-object
        // reader had: a row attached to this job may name another job's object.
        // Report::storageKey() re-derives the key and refuses anything that is
        // not this report's own. CreateJobReportBinaries catches this exception
        // per report and continues, so one bad row costs one entry, not the zip.
        $key = $report->storageKey();

        if (null === $key) {
            throw new ReportDownloadException('Path is not a readable object key');
        }

        $file = Storage::disk(Report::S3DISK)->get($key);

        if ( ! $file) {
            throw new ReportDownloadException('Path not found');
        }

        return $file;
    }

    /**
     * @throws \App\Modules\Report\Exception\ReportDownloadException
     */
    public function getZipPath(Job $job): string
    {
        try {
            return Storage::disk(Report::S3DISK)->path($this->getReplacedZipPath($job));
        } catch (Exception $exception) {
            Log::error('1695907099: Failed to get zip path', [
                'job' => $job->id,
                'disk' => Report::S3DISK,
                'exception' => $exception->getMessage(),
            ]);

            throw new ReportDownloadException('Failed to get zip path');
        }
    }

    public function getS3Bucket(Report $report): ?string
    {

        if (Str::contains($report->path, Report::S3PREFIX)) {
            return Str::before(Str::after($report->path, Report::S3PREFIX), '/');
        }

        return null;
    }

    public function getS3Path(Report $report): string
    {
        if (Str::contains($report->path, Report::S3PREFIX)) {
            return Str::remove(Report::S3PREFIX, $report->path);
        }

        return $report->path;
    }

    public function getReplacedZipPath(Job $job): string
    {
        return Str::replace('%id%', $job->id, 'job_%id%_reports.zip');
    }

    public function getReplacedPath(Job $job): string
    {
        return Str::replace('%id%', $job->id, 'job_%id%_reports');
    }
}
