<?php

namespace App\Modules\Report\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Exception\ReportDownloadException;
use App\Modules\Report\Model\Report;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * @throws \App\Modules\Report\Exception\ReportDownloadException
     */
    public function getRemotePath(Report $report): string
    {
        try {
            return Storage::disk('s3')->path($report->path);
        } catch (Exception $exception) {
            Log::error('1695907089: Failed to get remote path', [
                'path' => $report->path,
                'exception' => $exception->getMessage(),
            ]);

            throw new ReportDownloadException('Failed to get remote path');
        }
    }

    /**
     * @throws \App\Modules\Report\Exception\ReportDownloadException
     */
    public function getFileByPath(string $path): ?string
    {
        $file = Storage::disk('s3')->get($path);

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
            return Storage::path('app/public/report-job-'.$job->id.'.zip');
        } catch (Exception $exception) {
            Log::error('1695907099: Failed to get zip path', [
                'job' => $job->id,
                'exception' => $exception->getMessage(),
            ]);

            throw new ReportDownloadException('Failed to get zip path');
        }
    }
}
