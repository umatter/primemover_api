<?php

namespace App\Http\Controllers\API\v1;

use App\Queue;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Resources\v1\QueueResource;
use App\Http\Resources\v1\QueueResourceCollection;

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
        $queues = Queue::with(['crawler', 'jobs'])->latest()->get();
        return new QueueResourceCollection($queues);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return QueueResourceCollection
     */
    public function store(Request $request): QueueResourceCollection
    {

        $this->validate($request, [
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $queue = Queue::create($data);
        $queues = Queue::all();
        return new QueueResourceCollection($queues, $queue);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Queue $queue
     * @return \Illuminate\Http\Response
     */
    public function show($id): QueueResource
    {
        $queue = Queue::with(['user', 'crawler'])->findOrFail($id);
        return new QueueResource($queue);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Queue $queue
     * @return \Illuminate\Http\Response
     */
    public function edit(Queue $queue)
    {
        //
        return;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Queue $queue
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Queue $queue)
    {
        $data = $request->all();
        //$data['started_at'] = Carbon::parse($data['started_at'])->toISOString();
        //$data['finished_at'] = Carbon::parse($data['finished_at'])->toISOString();
        $queue->update($data);
        return $queue;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Queue $queue
     * @return \Illuminate\Http\Response
     */
    public function destroy(Queue $queue): QueueResourceCollection
    {
        if ($queue) {
            $queue->delete();
            //return Queue::latest()->get();
            $queues = Queue::latest()->paginate(5);
            return new QueueResourceCollection($queues);
        }
        return false;
    }
}
