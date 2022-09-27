<?php

namespace App\Modules\Queue\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class QueueController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $crawlerId = $request->input('crawler');
        $sortBy = $request->input('sortBy');
        $queues = QueryBuilder::for(Queue::class)
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

        return ResourceCollection::make($queues);
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();
        $data['user_id'] = Auth::id() ?? 1;

        $queue = Queue::query()->create($data);

        return JsonResource::make($queue);
    }

    public function show($id): JsonResource
    {
        $queue = Queue::with(['jobs'])->findOrFail($id);

        return JsonResource::make($queue);
    }

    public function job($id): JsonResource
    {
        $job = Job::query()->findOrFail($id);
        $queue = $job->queue()->first();

        return JsonResource::make($queue);
    }

    public function processed(): ResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('processed', 1)->get();

        return ResourceCollection::make($queue);
    }

    public function unprocessed(): ResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('processed', 0)->get();

        return ResourceCollection::make($queue);
    }

    public function reviewed(): ResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('reviewed', 1)->get();

        return ResourceCollection::make($queue);
    }

    public function unreviewed(): ResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('reviewed', 0)->where('processed', 1)->get();

        return ResourceCollection::make($queue);
    }

    public function experimentUnreviewed(Experiment $experiment): ResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 0)
            ->where('processed', 1)
            ->get();

        return ResourceCollection::make($queue);
    }

    public function experimentReviewed(Experiment $experiment): ResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 1)
            ->get();

        return ResourceCollection::make($queue);
    }

    public function experimentUnprocessed(Experiment $experiment): ResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 0)
            ->get();

        return ResourceCollection::make($queue);
    }

    public function experimentProcessed(Experiment $experiment): ResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 1)
            ->get();

        return ResourceCollection::make($queue);
    }

    public function edit(Queue $queue): JsonResource
    {
        return JsonResource::make($queue);
    }

    public function update(Request $request, Queue $queue): JsonResource
    {
        $data = $request->all();
        $queue->update($data);

        return JsonResource::make($queue);
    }

    /**
     * @throws Exception
     */
    public function destroy(Queue $queue): ResourceCollection
    {
        $queue->delete();

        return ResourceCollection::make(
            Queue::with(['crawler', 'jobs'])->latest()->get()
        );
    }
}
