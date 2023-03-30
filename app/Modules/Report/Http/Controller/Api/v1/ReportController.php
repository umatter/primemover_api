<?php

namespace App\Modules\Report\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Repository\ReportRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function __construct(protected ReportRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $reports = $this->repository->getData();

        return ResourceCollection::make($reports);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResource
    {
        $this->validate($request, [
            'job_id' => 'required',
            'mimetype' => 'required',
        ]);

        $report = $this->repository->save($request->all());

        return JsonResource::make($report);
    }

    public function show(Report $report): JsonResource
    {
        return JsonResource::make($report);
    }

    public function destroy(Report $report): bool
    {
        return $this->repository->delete($report);
    }
}
