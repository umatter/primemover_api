<?php

namespace App\Modules\Job\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Http\Request\RunnerJobUpdateRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class RunnerJobController extends Controller
{
    public function __construct(protected JobRepository $repository)
    {
    }

    public function update(RunnerJobUpdateRequest $request, Job $job): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $job));
    }
}
