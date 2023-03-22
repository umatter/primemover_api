<?php

namespace App\Modules\Job\Http\Service;

use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class JobService
{
    protected Job $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }
    
    public function getData($queueId): LengthAwarePaginator
    {
        $jobs = QueryBuilder::for($this->job)
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
        
        return $jobs;
    }
    
    public function getById($id): Job
    {
        $job = $this->job->with([
            'behaviors',
            'reports:id,job_id,path,filename,created_at,updated_at',
        ])->findOrFail($id);

        return $job;
    }

    public function save(array $data): Job
    {
        return $this->job->query()->create($data);
    }

    public function update(array $data, Job $job): Job
    {
        $job->update($data);

        return $job;
    }

    public function delete(Job $job): void
    {
        $job->delete();
    }
    
}