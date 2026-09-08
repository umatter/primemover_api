<?php

namespace App\Modules\Queue\Repository;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Query\StatusQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class QueueRepository
{
    public function __construct(protected Queue $queue) {}

    /**
     * GET /queues.
     *
     * ?sortBy=severity (and -severity) orders the page worst-first by what the
     * status_code MEANS rather than by how it is spelled -- 'failed' and
     * 'failure' are one outcome and have to land together. It is applied as the
     * PRIMARY ordering (StatusQuery::applySeveritySortBy reorders first),
     * because Spatie has already put defaultSort('-id') on the builder by the
     * time this method runs and appending to that would sort by id and then, to
     * no effect, by severity.
     *
     * Every other ?sortBy= value keeps the behaviour it had, appended to the
     * default sort, warts and all -- changing that is not this change.
     * ?sort=severity works too, through the Spatie sort pipeline; both spellings
     * exist because this endpoint has always read its own sortBy parameter.
     */
    public function all($crawlerId, $sortBy): LengthAwarePaginator
    {
        return Queue::queryBuilder()
            ->visibleTo()
            ->when($crawlerId, fn (Builder $query) => $query->where('crawler_id', $crawlerId))
            ->when($sortBy, function (Builder $query) use ($sortBy): void {
                if (StatusQuery::isSeveritySort($sortBy)) {
                    StatusQuery::applySeveritySortBy($query, $sortBy, (new Queue())->getTable());

                    return;
                }

                $query->orderBy($sortBy);
            })
            ->with(['crawler'])
            ->fastPaginate();
    }

    public function save(array $data): Queue|Model
    {
        return $this->queue->query()->create($data);
    }

    public function update(array $data, Queue $queue): Queue
    {
        $queue->update($data);

        return $queue->fresh();
    }

    public function delete(Queue $queue): Collection
    {
        $queue->delete();

        return $this->queue->query()->visibleTo()->with(['crawler', 'jobs'])->get();
    }

    public function job($id): Queue
    {
        // Scoped: this resolves a job id straight from the URL
        // (GET queues/job/{jobid}) with no policy behind it.
        $job = Job::query()->visibleTo()->findOrFail($id);

        return $job->queue()->first();
    }

    /**
     * The queues-* families below all carry the job-outcome rollup.
     *
     * Until this change the rollup rode on GET /queues alone, which meant the
     * only endpoints a GUI uses to show a set of runs -- processed,
     * unprocessed, reviewed, unreviewed and their four experiment-scoped twins
     * -- could show the runs but not their outcome without a second request per
     * run.
     *
     * It is COUNTED IN PHP here, not asked for in SQL. These eight lists are
     * unpaginated and already eager-load `jobs` in full, so every one of the
     * seven numbers is derivable from rows that are about to be serialised
     * anyway. The scope would add seven correlated count subselects per row on
     * top of that: measured on the rehearsal copy of production (735 queues /
     * 31,574 jobs) they cost 87 ms against 2 ms to fetch every job row they
     * were recounting. Queue::rollupFromLoadedJobs() produces the identical
     * seven attributes for no query at all.
     *
     * GET /queues (paginated, no `jobs` relation) and GET /queues/{id} keep the
     * SQL rollup, because there the jobs are not all in memory.
     */
    public function processed(): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])->where('processed', 1)->get()
        );
    }

    public function unprocessed(): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])->where('processed', 0)->get()
        );
    }

    public function reviewed(): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])->where('reviewed', 1)->get()
        );
    }

    public function unreviewed(): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])->where('reviewed', 0)->where('processed', 1)->get()
        );
    }

    public function experimentUnreviewed($experiment): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])
                ->whereHas('crawler', function ($crawler) use ($experiment): void {
                    $crawler->where('experiment_id', $experiment->id);
                })
                ->where('reviewed', 0)
                ->where('processed', 1)
                ->get()
        );
    }

    public function experimentReviewed($experiment): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])
                ->whereHas('crawler', function ($crawler) use ($experiment): void {
                    $crawler->where('experiment_id', $experiment->id);
                })
                ->where('reviewed', 1)
                ->get()
        );
    }

    public function experimentUnprocessed($experiment): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])
                ->whereHas('crawler', function ($crawler) use ($experiment): void {
                    $crawler->where('experiment_id', $experiment->id);
                })
                ->where('processed', 0)
                ->get()
        );
    }

    public function experimentProcessed($experiment): Collection
    {
        return self::withLoadedJobRollup(
            $this->queue->query()->visibleTo()->with(['jobs'])
                ->whereHas('crawler', function ($crawler) use ($experiment): void {
                    $crawler->where('experiment_id', $experiment->id);
                })
                ->where('processed', 1)
                ->get()
        );
    }

    /**
     * Put the job-outcome rollup on every queue in a list whose `jobs` relation
     * is already loaded.
     *
     * @param  Collection<int, Queue> $queues
     * @return Collection<int, Queue>
     */
    private static function withLoadedJobRollup(Collection $queues): Collection
    {
        return $queues->each(fn (Queue $queue) => $queue->rollupFromLoadedJobs());
    }
}
