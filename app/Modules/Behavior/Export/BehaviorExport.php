<?php

namespace App\Modules\Behavior\Export;

use App\Modules\Behavior\Model\Behavior;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BehaviorExport implements FromQuery, WithHeadings
{
    use Exportable;

    public function __construct(protected Experiment $experiment) {}

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Value',
            'Active',
            'Updated At',
            'Created At',
            'User ID',
            'Job ID',
        ];
    }

    public function query()
    {
        $crawlers = $this->experiment->crawlers;
        $crawlerIds = $crawlers->pluck('id')->toArray();

        $queues = Queue::query()->whereIn('crawler_id', $crawlerIds)->get();
        $queueIds = $queues->pluck('id')->toArray();

        $job = Job::query()->whereIn('queue_id', $queueIds)->get();
        $jobIds = $job->pluck('id')->toArray();

        return Behavior::query()->whereIn('job_id', $jobIds);
    }
}
