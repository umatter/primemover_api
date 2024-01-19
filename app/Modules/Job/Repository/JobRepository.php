<?php

namespace App\Modules\Job\Repository;

use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class JobRepository
{
    public function __construct(protected Job $job)
    {
    }

    public function all($queueId): LengthAwarePaginator
    {
        return Job::queryBuilder()
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
