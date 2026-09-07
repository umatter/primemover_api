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
     * withJobOutcomeCounts() adds seven count SUBSELECTS to the one query, not
     * seven queries and not one per row, so a list that was already fetching
     * every job of every run now also states what those jobs came to. Until
     * this change the rollup rode on GET /queues alone, which meant the only
     * endpoints a GUI uses to show a set of runs -- processed, unprocessed,
     * reviewed, unreviewed and their four experiment-scoped twins -- could show
     * the runs but not their outcome without a second request per run.
     */
    public function processed(): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])->where('processed', 1)->get();
    }

    public function unprocessed(): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])->where('processed', 0)->get();
    }

    public function reviewed(): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])->where('reviewed', 1)->get();
    }

    public function unreviewed(): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])->where('reviewed', 0)->where('processed', 1)->get();
    }

    public function experimentUnreviewed($experiment): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 0)
            ->where('processed', 1)
            ->get();
    }

    public function experimentReviewed($experiment): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 1)
            ->get();
    }

    public function experimentUnprocessed($experiment): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 0)
            ->get();
    }

    public function experimentProcessed($experiment): Collection
    {
        return $this->queue->query()->visibleTo()->withJobOutcomeCounts()->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 1)
            ->get();
    }
}
