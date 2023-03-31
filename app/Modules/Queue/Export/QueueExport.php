<?php

namespace App\Modules\Queue\Export;

use App\Modules\Experiment\Model\Experiment;
use App\Modules\Queue\Model\Queue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QueueExport implements FromQuery, WithHeadings
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
            'Active',
            'New',
            'Processed',
            'Processing',
            'Reviewed',
            'Status Code',
            'Status Message',
            'Order',
            'Start At',
            'Updated At',
            'Created At',
            'Started At',
            'Finished At',
            'User ID',
            'Crawler ID',
        ];
    }

    public function query()
    {
        $crawlers = $this->experiment->crawlers;
        $crawlersIds = $crawlers->pluck("id")->toArray();

        return Queue::query()->whereIn('crawler_id', $crawlersIds);
    }
}