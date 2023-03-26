<?php

namespace App\Modules\Queue\Http\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class QueueService
{
    protected Queue $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }
    
    public function getData($crawlerId, $sortBy): LengthAwarePaginator
    {
        $queues = QueryBuilder::for($this->queue)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->when($crawlerId, fn (Builder $query) => $query->where('crawler_id', $crawlerId))
            ->when($sortBy, fn (Builder $query) => $query->orderBy($sortBy))
            ->with(['crawler'])
            ->latest()
            ->fastPaginate();

        return $queues;
    }

    public function save(array $data): Queue
    {
        return $this->queue->query()->create($data);
    }

    public function update(array $data, Queue $queue): Queue
    {
        $queue->update($data);

        return $queue;
    }

    public function delete(Queue $queue): Collection
    {
        $queue->delete();

        return $this->queue->with(['crawler', 'jobs'])->latest()->get();
    }

    public function job($id): Queue
    {
        $job = Job::query()->findOrFail($id);
        $queue = $job->queue()->first();

        return $queue;
    }

    public function processed(): Collection
    {
        $queue = $this->queue->with(['jobs'])->where('processed', 1)->get();

        return $queue;
    }

    public function unprocessed(): Collection
    {
        $queue = $this->queue->with(['jobs'])->where('processed', 0)->get();

        return $queue;
    }

    public function reviewed(): Collection
    {
        $queue = $this->queue->with(['jobs'])->where('reviewed', 1)->get();

        return $queue;
    }

    public function unreviewed(): Collection
    {
        $queue = $this->queue->with(['jobs'])->where('reviewed', 0)->where('processed', 1)->get();

        return $queue;
    }

    public function experimentUnreviewed($experiment): Collection
    {
        $queue = $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 0)
            ->where('processed', 1)
            ->get();

        return $queue;
    }

    public function experimentReviewed($experiment): Collection
    {
        $queue = $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 1)
            ->get();

        return $queue;
    }

    public function experimentUnprocessed($experiment): Collection
    {
        $queue = $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 0)
            ->get();

        return $queue;
    }

    public function experimentProcessed($experiment): Collection
    {
        $queue = $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 1)
            ->get();

        return $queue;
    }
    
}