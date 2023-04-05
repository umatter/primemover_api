<?php

namespace App\Modules\Report\Repository;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportRepository
{
    public function __construct(protected Report $report)
    {
    }

    public function getData(): LengthAwarePaginator
    {
        return $this->report->query()->latest()->paginate(10);
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
}
