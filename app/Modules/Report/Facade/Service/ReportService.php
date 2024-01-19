<?php

namespace App\Modules\Report\Facade\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string      getFileByPath(Report $report)
 * @method static string      getZipPath(Job $job)
 * @method static null|string getS3Bucket(Report $report)
 * @method static string      getS3Path(Report $report)
 * @method static string      getReplacedZipPath(Job $job)
 * @method static string      getReplacedPath(Job $job)
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
