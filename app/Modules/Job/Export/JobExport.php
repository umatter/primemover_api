<?php

namespace App\Modules\Job\Export;

use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class JobExport implements FromQuery, WithHeadings
{
    use Exportable;

    public function __construct(protected Experiment $experiment)
    {
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Type',
            'Status Code',
            'Status Message',
            'Order',
            'Active',
            'Updated At',
            'Created At',
            'Started At',
            'Finished At',
            'User ID',
            'Queue ID',
            'Captcha Found',
            'Captcha Solved',
        ];
    }

    public function query()
    {
        $crawlers = $this->experiment->crawlers;
        $crawlerIds = $crawlers->pluck('id')->toArray();

        $queues = Queue::query()->whereIn('crawler_id', $crawlerIds)->get();
        $queueIds = $queues->pluck('id')->toArray();

        return Job::query()->whereIn('queue_id', $queueIds);
    }
}
