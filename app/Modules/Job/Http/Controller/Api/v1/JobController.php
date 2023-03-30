<?php

namespace App\Modules\Job\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Http\Request\UpdateJobRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Service\JobService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class JobController extends Controller
{
    public function __construct(protected JobService $jobService)
    {
    }

    public function index(Request $request): ResourceCollection
    {
        $queueId = $request->input('queue');
        $jobs = $this->jobService->getData($queueId);

        return ResourceCollection::make($jobs);
    }

    public function store(StoreJobRequest $request): JsonResource
    {
        $job = $this->jobService->save($request->validated());

        return JsonResource::make($job);
    }

    public function show($id): JsonResource
    {
        $job = $this->jobService->getById($id);

        return JsonResource::make($job);
    }

    public function edit(Job $job): JsonResource
    {
        return JsonResource::make($job);
    }

    public function update(UpdateJobRequest $request, Job $job): JsonResource
    {
        return JsonResource::make($this->jobService->update($request->validated(), $job));
    }

    /**
     * @throws Exception
     */
    public function destroy(Job $job): ResourceCollection
    {
        $this->jobService->delete($job);

        return ResourceCollection::make(
            Job::with(['behaviors', 'reports:id,job_id,path,filename'])->latest()->get()
        );
    }
}
