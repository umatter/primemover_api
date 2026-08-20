<?php

namespace App\Modules\Queue\Repository;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class QueueRepository
{
    public function __construct(protected Queue $queue)
    {
    }

    public function all($crawlerId, $sortBy): LengthAwarePaginator
    {
        return Queue::queryBuilder()
            ->visibleTo()
            ->when($crawlerId, fn (Builder $query) => $query->where('crawler_id', $crawlerId))
            ->when($sortBy, fn (Builder $query) => $query->orderBy($sortBy))
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

        return $this->queue->with(['crawler', 'jobs'])->get();
    }

    public function job($id): Queue
    {
        $job = Job::query()->findOrFail($id);

        return $job->queue()->first();
    }

    public function processed(): Collection
    {
        return $this->queue->with(['jobs'])->where('processed', 1)->get();
    }

    public function unprocessed(): Collection
    {
        return $this->queue->with(['jobs'])->where('processed', 0)->get();
    }

    public function reviewed(): Collection
    {
        return $this->queue->with(['jobs'])->where('reviewed', 1)->get();
    }

    public function unreviewed(): Collection
    {
        return $this->queue->with(['jobs'])->where('reviewed', 0)->where('processed', 1)->get();
    }

    public function experimentUnreviewed($experiment): Collection
    {
        return $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 0)
            ->where('processed', 1)
            ->get();
    }

    public function experimentReviewed($experiment): Collection
    {
        return $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 1)
            ->get();
    }

    public function experimentUnprocessed($experiment): Collection
    {
        return $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 0)
            ->get();
    }

    public function experimentProcessed($experiment): Collection
    {
        return $this->queue->with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment): void {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 1)
            ->get();
    }
}
