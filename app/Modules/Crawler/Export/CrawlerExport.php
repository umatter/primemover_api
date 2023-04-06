<?php

namespace App\Modules\Crawler\Export;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CrawlerExport implements FromQuery, WithHeadings
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
            'Testing',
            'Update',
            'updated_at',
            'created_at',
            'experiment_id',
            'user_id',
            'proxy_id',
            'agent_id',
            'configuration_id',
        ];
    }

    public function query()
    {
        return Crawler::query()->where('experiment_id', $this->experiment->id);
    }
}
