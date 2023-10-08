<?php

namespace App\Modules\Report\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Facade\ReportRepository;
use App\Modules\Report\Http\Request\ReportStoreRequest;
use App\Modules\Report\Model\Report;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function index(): ResourceCollection
    {
        $reports = ReportRepository::getData();

        return ResourceCollection::make($reports);
    }

    public function store(ReportStoreRequest $request): JsonResource
    {
        $report = ReportRepository::save($request->validated());

        return JsonResource::make($report);
    }

    public function show(Report $report): JsonResource
    {
        return JsonResource::make($report);
    }

    public function destroy(Report $report): bool
    {
        return ReportRepository::delete($report);
    }

    /**
     * @throws Exception
     */
    public function download(Job $job): \Symfony\Component\HttpFoundation\Response
    {
        $response = ReportRepository::download($job);

        if ($response) {
            return Response::download($response, 'report-job-'.$job->id.'.zip', [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend();
        }

        return Response::noContent();
    }
}
