<?php

namespace App\Modules\Report\Pipe;

use App\Modules\Report\Model\Report;
use Closure;
use Exception;
use Illuminate\Support\Facades\Log;

class TakeOnlyReportsWithPathOrFail
{
    /**
     * @throws Exception
     */
    public function handle(JobReportData $jobReportData, Closure $next): mixed
    {
        $reportsWithPath = $jobReportData->getJob()
            ->reports()
            ->get()
            ->map(function (Report $report) {
                if ( ! $report->path) {
                    Log::debug('1695907102: Report has no path', [
                        'report' => $report->id,
                    ]);
                }

                return $report;
            })->filter(fn (Report $report) => $report->path);

        if (0 === $reportsWithPath->count()) {
            throw new Exception('Job has no reports with valid path');
        }

        return $next($jobReportData->setReports($reportsWithPath));
    }
}
