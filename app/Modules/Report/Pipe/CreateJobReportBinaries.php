<?php

namespace App\Modules\Report\Pipe;

use App\Facades\Factories\ZipFactory;
use App\Modules\Report\Exception\ReportDownloadException;
use App\Modules\Report\Facade\Service\ReportService;
use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateJobReportBinaries
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(JobReportData $jobReportData, Closure $next): mixed
    {

        $job = $jobReportData->getJob();

        foreach ($jobReportData->getReports() as $report) {
            try {
                $filename = basename($report->path);
                $file = ReportService::getFileByPath($report);
                $path = ReportService::getReplacedPath($job)."/{$filename}";

                Storage::disk('public')->put($path, $file);
            } catch (ReportDownloadException) {
                Log::error('1695907101: Failed to download report', [
                    'job' => $job->id,
                    'report' => $report->id,
                ]);

                continue;
            }
        }

        $zipArchive = ZipFactory::create();
        $zipPath = ReportService::getZipPath($job);
        $zipPathPublic = Storage::disk('public')->path($zipPath);

        return $next(
            $jobReportData
                ->setZipArchive($zipArchive)
                ->setZipPath($zipPathPublic)
        );
    }
}
