<?php

namespace App\Modules\Report\Facade;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @method static Response|BinaryFileResponse|null download(Job $job)
 * @method static LengthAwarePaginator getData()
 * @method static Report save(array $data)
 * @method static bool delete(Report $report)
 *
 * @see \App\Modules\Report\Repository\ReportRepository
 */
class ReportRepository extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Report\Repository\ReportRepository::class;
    }
}
