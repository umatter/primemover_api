<?php

namespace App\Modules\Report\Http\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportService
{
    protected Report $report;

    public function __construct(Report $report)
    {
        $this->report = $report;
    }
    
    public function getData(): LengthAwarePaginator
    {
        $reports = $this->report->query()->latest()->paginate(10);

        return $reports;
    }

    public function save(array $data): Report
    {
        $env = env('APP_ENV');
        $dev = env('APP_DEV_API_URL');
        $prod = env('APP_PROD_API_URL');
        $path = $env === 'local' ? $dev : $prod;

        $job = Job::findOrFail($data['job_id']);
        $desc = 'Bot: ' . $job->queue->crawler->name . ' | Job-Name: ' . $job->name . ', Job-ID: ' . $job->id . ', Job-Type: ' . $job->type . ')';

        $data['job_id'] = $data['job_id'];
        $data['description'] = isset($data["description"]) ? $data["description"] : $desc;
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        $data['filecontent'] = $data["filecontent"] ?? null;
        $data['filename'] = $data["filename"] ?? null;
        $data['path'] = $data["path"] ?? null;
        $data['mimetype'] = $data["mimetype"] ?? null;

        // $this->storeFile();

        $report = $this->report->create($data);
        if ($report && $data["filecontent"]) {
            $report->path = $path . '/file/' . $report->id;
        }
        $report->save();

        return $report;
    }

    public function delete(Report $report): bool
    {
        return $report->delete();
    }
    
}