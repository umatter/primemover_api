<?php

namespace App\Modules\Report\Facade;

use App\Modules\Job\Model\Job;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @method static Response|BinaryFileResponse|null download(Job $job)
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
