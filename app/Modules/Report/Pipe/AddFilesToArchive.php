<?php

namespace App\Modules\Report\Pipe;

use Closure;
use Exception;
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

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $folderPath = storage_path('app/public/report-job-'.$job->id.'/');

            $files = glob($folderPath.'*');
            foreach ($files as $file) {
                $filename = basename($file);
                if (is_file($file)) {
                    $zip->addFile($file, $filename);
                }
            }

            $zip->close();

            array_map('unlink', glob("{$folderPath}*.*"));
            rmdir($folderPath);
        } else {
            throw new Exception('Unable to create ZIP archive');
        }

        return $next($jobReportData);
    }
}
