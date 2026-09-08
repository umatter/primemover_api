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
     * Until 2026-09-07 the rollup rode on GET /queues alone, which meant the
     * only endpoints a GUI uses to show a set of runs -- processed,
     * unprocessed, reviewed, unreviewed and their four experiment-scoped twins
     * -- could show the runs but not their outcome without a second request per
     * run.
     *
     * It was then counted in PHP off an eager-loaded `jobs` relation, on the
     * reasoning that these lists serialise every job anyway so the seven
     * numbers are free. The reasoning was sound and the premise was the bug.
     * Serialising every job of every run is what a list endpoint must not do:
     * on production (735 runs / 31,574 jobs) hydrating them peaked at 460.5 MB
     * against a 128 MB limit, so all three top-level lists answered 500 --
     * emitting no rollup, no runs and no error anyone would see, since nothing
     * in the GUI, the runner or the orchestrator calls them.
     *
     * So they now look like GET /queues, which had the shape right all along:
     * PAGINATED, with the rollup counted in SQL over the page, and no `jobs`
     * relation. Peak memory becomes O(page) instead of O(every job ever run),
     * and the seven subselects -- 87 ms across the whole table, and a small
     * fraction of that across fifteen rows -- are the cheap half of the trade.
     *
     * `?per_page=` follows the convention already used by the crawler and agent
     * lists; `data` keeps its meaning, and `links`/`meta` are new.
     */
    public function processed(): LengthAwarePaginator
    {
        return $this->list()->where('processed', 1)->fastPaginate(self::perPage());
    }

    public function unprocessed(): LengthAwarePaginator
    {
        return $this->list()->where('processed', 0)->fastPaginate(self::perPage());
    }

    public function reviewed(): LengthAwarePaginator
    {
        return $this->list()->where('reviewed', 1)->fastPaginate(self::perPage());
    }

    public function unreviewed(): LengthAwarePaginator
    {
        return $this->list()->where('reviewed', 0)->where('processed', 1)->fastPaginate(self::perPage());
    }

    public function experimentUnreviewed($experiment): LengthAwarePaginator
    {
        return $this->list($experiment)
            ->where('reviewed', 0)
            ->where('processed', 1)
            ->fastPaginate(self::perPage());
    }

    public function experimentReviewed($experiment): LengthAwarePaginator
    {
        return $this->list($experiment)->where('reviewed', 1)->fastPaginate(self::perPage());
    }

    public function experimentUnprocessed($experiment): LengthAwarePaginator
    {
        return $this->list($experiment)->where('processed', 0)->fastPaginate(self::perPage());
    }

    public function experimentProcessed($experiment): LengthAwarePaginator
    {
        return $this->list($experiment)->where('processed', 1)->fastPaginate(self::perPage());
    }

    /**
     * The shared shape of the eight lists: visible runs, the SQL rollup, and
     * NO `jobs` relation.
     *
     * The missing eager load is the fix, not an oversight -- see the note
     * above. A run's jobs are one request away at GET /queues/{id}, which is
     * where a caller that actually wants them should ask.
     */
    private function list($experiment = null): Builder
    {
        return $this->queue->query()
            ->visibleTo()
            ->withJobOutcomeCounts()
            ->when(
                null !== $experiment,
                fn (Builder $query) => $query->whereHas(
                    'crawler',
                    fn (Builder $crawler) => $crawler->where('experiment_id', $experiment->id)
                )
            );
    }

    /**
     * Same parameter and same default as the crawler and agent lists.
     */
    private static function perPage(): int
    {
        return (int) request()->input('per_page', 15);
    }
}
