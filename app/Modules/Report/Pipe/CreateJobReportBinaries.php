<?php

namespace App\Modules\Report\Pipe;

use App\Facades\Factories\ZipFactory;
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

        try {

            foreach ($job->reports()->get() as $report) {
                $path = Storage::disk('s3')->path($report->path);
                $file = Storage::disk('s3')->get($path);
                $filename = basename($path);

                Storage::disk('public')->put('report-job-'.$job->id."/{$filename}", $file);
            }
        } catch (Throwable $exception) {
            Log::error('1695907087: Failed to create binary', [
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        $zip = ZipFactory::create();
        $zipPath = Storage::path('app/public/report-job-'.$job->id.'.zip');

        return $next(
            $jobReportData->setZipArchive($zip)->setZipPath($zipPath)
        );
    }
}
