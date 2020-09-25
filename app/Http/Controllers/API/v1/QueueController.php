<?php

namespace App\Http\Controllers\API\v1;

use App\Job;
use App\Queue;
use Exception;
use Illuminate\Http\Request;
use App\Http\Resources\v1\QueueResource;
use App\Http\Resources\v1\QueueResourceCollection;
use Illuminate\Validation\ValidationException;

class QueueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return QueueResourceCollection
     */
    public function index(Request $request): QueueResourceCollection
    {
        $keyword = $request->input('keyword');
        $queues = Queue::with(
            [
                'user',
                'crawler',
                'jobs'
            ]
        )->latest()->get();
        return new QueueResourceCollection($queues);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return QueueResourceCollection
     */
    public function create()
    {
        //
        return new QueueResourceCollection(new Queue);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return QueueResource
     * @throws ValidationException
     */
    public function store(Request $request): QueueResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $queue = Queue::create($data);
        return new QueueResource($queue);
    }

    /**
     * Show a resource.
     * @param $id
     * @return QueueResource
     */
    public function show($id): QueueResource
    {
        $queue = Queue::with(['user', 'jobs'])->findOrFail($id);
        return new QueueResource($queue);
    }

    public function job($id): QueueResource
    {
        $job = Job::findOrFail($id);
        $queue = $job->queue()->first();

        return new QueueResource($queue);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Queue $queue
     * @return mixed
     */
    public function edit(Queue $queue): QueueResource
    {
        return new QueueResource($queue);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Queue $queue
     * @return QueueResource
     */
    public function update(Request $request, Queue $queue): QueueResource
    {
        $data = $request->all();
        $queue->update($data);
        return new QueueResource($queue);
    }

    /**
     * Remove the specified resource from storage.
     * @param Queue $queue
     * @return QueueResourceCollection
     * @throws Exception
     */
    public function destroy(Queue $queue): QueueResourceCollection
    {
        $queue->delete();
        return new QueueResourceCollection(
            Queue::with(
                [
                    'user',
                    'crawler',
                    'jobs'
                ]
            )->latest()->get());
    }
}
