<?php

namespace App\Modules\Job\Repository;

use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class JobRepository
{
    public function __construct(protected Job $job)
    {
    }

    public function all($queueId): LengthAwarePaginator
    {
        return QueryBuilder::for($this->job)
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('queue'),
            ])
            ->when($queueId, fn (Builder $query) => $query->where('queue_id', $queueId))
            ->with(['queue', 'reports'])
            ->fastPaginate();
    }

    public function getById($id): Job|Model
    {
        return $this->job->with([
            'behaviors',
            'reports:id,job_id,path,filename,created_at,updated_at',
        ])->findOrFail($id);
    }

    public function save(array $data): Job|Model
    {
        return $this->job->query()->create($data);
    }

    public function update(array $data, Job $job): Job
    {
        $job->update($data);

        return $job->fresh();
    }

    public function delete(Job $job): void
    {
        $job->delete();
    }
}
