<?php

namespace App\Modules\Report\Pipe;

use App\Modules\Report\Facade\Service\ReportService;
use Closure;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class AddFilesToArchive
{
    /**
     * @throws Exception
     */
    public function handle(JobReportData $jobReportData, Closure $next): mixed
    {
        $zip = $jobReportData->getZipArchive();
        $zipPath = $jobReportData->getZipPath();
        $job = $jobReportData->getJob();
        $path = ReportService::getReplacedPath($job);
        $publicDisk = Storage::disk('public');

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            Collection::make($publicDisk->files($path))
                ->map(fn (string $file) => $publicDisk->path($file))
                ->filter(fn (string $file) => ! $publicDisk->exists($file))
                ->each(fn (string $file) => $zip->addFile($file, basename($file)));

            $zip->close();
            $publicDisk->deleteDirectory($path);
        } else {
            throw new Exception('Unable to create ZIP archive');
        }

        return $next($jobReportData);
    }
}
