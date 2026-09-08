<?php

namespace App\Modules\Job\Repository;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Query\StatusQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class JobRepository
{
    public function __construct(protected Job $job) {}

    /**
     * GET /jobs.
     *
     * ?sortBy=severity (and -severity) orders the page by what the status_code
     * MEANS rather than by how it is spelled: the four spellings of failure in
     * this table ('failure', 'failed', 'error', 'fatal') are one outcome and
     * have to land together, which ordering the raw string cannot do.
     *
     * It replaces the default ordering rather than being appended to it --
     * Spatie has already applied defaultSort('-id') by the time this runs, and
     * "order by id desc, severity" sorts by severity not at all. Any other
     * ?sortBy= value is ignored here exactly as it always was; ?sort= is the
     * validated way to order this endpoint by anything else.
     */
    public function all($queueId, ?int $perPage = null, mixed $sortBy = null): LengthAwarePaginator
    {
        return Job::queryBuilder()
            ->visibleTo()
            ->when($queueId, fn (Builder $query) => $query->where('queue_id', $queueId))
            ->when(StatusQuery::isSeveritySort($sortBy), fn (Builder $query) => StatusQuery::applySeveritySortBy($query, $sortBy, (new Job())->getTable()))
            ->with(['queue:id,name,crawler_id'])
            ->fastPaginate($perPage ?? request()->input('per_page', 15));
    }

    public function getById($id): Job|Model
    {
        // visibleTo() before findOrFail so a foreign id 404s rather than
        // leaking the row: JobController::show() resolves through here
        // instead of route-model binding, so no policy runs on it.
        return $this->job->query()
            ->visibleTo()
            ->with([
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
