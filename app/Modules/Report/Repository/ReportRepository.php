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

// TODO Refactor asap
class ReportRepository
{
    public function __construct(protected Report $report)
    {
    }

    public function all(): LengthAwarePaginator
    {
        return $this->report
            ->query()
            ->paginate(10);
    }

    public function save(array $data): Report
    {
        $env = env('APP_ENV');
        $dev = env('APP_DEV_API_URL');
        $prod = env('APP_PROD_API_URL');
        $path = 'local' === $env ? $dev : $prod;

        $job = Job::findOrFail($data['job_id']);
        $desc = 'Bot: '.$job->queue->crawler->name.' | Job-Name: '.$job->name.', Job-ID: '.$job->id.', Job-Type: '.$job->type.')';

        $data['description'] ??= $desc;
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        $data['filecontent'] ??= null;
        $data['filename'] ??= null;
        $data['path'] ??= null;
        $data['mimetype'] ??= null;

        // $this->storeFile();

        $report = $this->report->create($data);
        if ($report && $data['filecontent']) {
            $report->path = $path.'/file/'.$report->id;
        }

        $report->save();

        return $report;
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
