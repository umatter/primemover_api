<?php

namespace App\Modules\Report\Repository;

use App\Facades\Factories\PipelineFactory;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Pipe\AddFilesToArchive;
use App\Modules\Report\Pipe\AssertJobHasReports;
use App\Modules\Report\Pipe\CreateJobReportBinaries;
use App\Modules\Report\Pipe\JobReportData;
use App\Modules\Report\Pipe\TakeOnlyReportsWithPathOrFail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReportRepository
{
    public function __construct(protected Report $report) {}

    public function all(): LengthAwarePaginator
    {
        return Report::queryBuilder()->paginate(10);
    }

    public function save(array $attributes): Report
    {
        return Report::create($attributes);
    }

    public function delete(Report $report): bool
    {
        return $report->delete();
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
}
