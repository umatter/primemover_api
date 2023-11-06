<?php

namespace App\Modules\Report\Pipe;

use Closure;
use Exception;

class AssertJobHasReports
{
    /**
     * @throws Exception
     */
    public function handle(JobReportData $jobReportData, Closure $next): mixed
    {
        if (0 === $jobReportData->getJob()->reports()->count()) {
            throw new Exception('Job has no reports attached');
        }

        return $next($jobReportData);
    }
}
