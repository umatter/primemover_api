<?php

namespace App\Http\Controllers\API\v1;

use App\Experiment;
use App\Job;
use App\Queue;
use Exception;
use Illuminate\Http\Request;
use App\Http\Resources\v1\QueueResource;
use App\Http\Resources\v1\QueueResourceCollection;
use Illuminate\Validation\ValidationException;

class QueueController extends Controller
{

    public function index(Request $request): QueueResourceCollection
    {
        $queues = Queue::with(
            [
                'user',
                'crawler',
                'jobs'
            ]
        )->latest()->get();
        return new QueueResourceCollection($queues);
    }

    public function create()
    {
        return new QueueResourceCollection(new Queue);
    }

    public function store(Request $request): QueueResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $queue = Queue::create($data);
        return new QueueResource($queue);
    }

    public function show($id): QueueResource
    {
        $queue = Queue::with(['jobs'])->findOrFail($id);
        return new QueueResource($queue);
    }

    public function job($id): QueueResource
    {
        $job = Job::findOrFail($id);
        $queue = $job->queue()->first();

        return new QueueResource($queue);
    }

    public function processed(): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('processed', 1)->get();
        return new QueueResourceCollection($queue);
    }

    public function unprocessed(): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('processed', 0)->get();
        return new QueueResourceCollection($queue);
    }

    public function reviewed(): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('reviewed', 1)->get();
        return new QueueResourceCollection($queue);
    }

    public function unreviewed(): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])->where('reviewed', 0)->where('processed', 1)->get();
        return new QueueResourceCollection($queue);
    }

    public function experimentUnreviewed(Experiment $experiment): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 0)
            ->where('processed', 1)
            ->get();
        return new QueueResourceCollection($queue);
    }

    public function experimentReviewed(Experiment $experiment): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('reviewed', 1)
            ->get();
        return new QueueResourceCollection($queue);
    }

    public function experimentUnprocessed(Experiment $experiment): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 0)
            ->get();
        return new QueueResourceCollection($queue);
    }

    public function experimentProcessed(Experiment $experiment): QueueResourceCollection
    {
        $queue = Queue::with(['jobs'])
            ->whereHas('crawler', function ($crawler) use ($experiment) {
                $crawler->where('experiment_id', $experiment->id);
            })
            ->where('processed', 1)
            ->get();
        return new QueueResourceCollection($queue);
    }

    public function edit(Queue $queue): QueueResource
    {
        return new QueueResource($queue);
    }

    public function update(Request $request, Queue $queue): QueueResource
    {
        $data = $request->all();
        $queue->update($data);
        return new QueueResource($queue);
    }

    public function destroy(Queue $queue): QueueResourceCollection
    {
        $queue->delete();
        return new QueueResourceCollection(
            Queue::with(
                [
                    'crawler',
                    'jobs'
                ]
            )->latest()->get());
    }
}
