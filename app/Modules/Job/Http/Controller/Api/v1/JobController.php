<?php

namespace App\Modules\Job\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Http\Request\UpdateJobRequest;
use App\Modules\Job\Model\Job;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class JobController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $queueId = $request->input('queue');
        $jobs = QueryBuilder::for(Job::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest()
            ->when($queueId, fn (Builder $query) => $query->where('queue_id', $queueId))
            ->with(['queue'])
            ->latest()
            ->fastPaginate();

        return ResourceCollection::make($jobs);
    }

    public function store(StoreJobRequest $request): JsonResource
    {
        $job = Job::query()->create($request->validated());

        return JsonResource::make($job);
    }

    public function show($id): JsonResource
    {
        $job = Job::with([
            'behaviors',
            'reports:id,job_id,path,filename,created_at,updated_at',
        ])->findOrFail($id);

        return JsonResource::make($job);
    }

    public function edit(Job $job): JsonResource
    {
        return JsonResource::make($job);
    }

    public function update(UpdateJobRequest $request, Job $job): JsonResource
    {
        $job->update($request->validated());

        return JsonResource::make($job);
    }

    /**
     * @throws Exception
     */
    public function destroy(Job $job): ResourceCollection
    {
        $job->delete();

        return ResourceCollection::make(
            Job::with(['behaviors', 'reports:id,job_id,path,filename'])->latest()->get()
        );
    }
}
