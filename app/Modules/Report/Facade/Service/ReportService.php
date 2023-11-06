<?php

namespace App\Modules\Report\Facade\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getRemotePath(Report $report)
 * @method static string getFileByPath(string $path)
 * @method static string getZipPath(Job $job)
 *
 * @see \App\Modules\Report\Service\ReportService
 */
class ReportService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Report\Service\ReportService::class;
    }
}
