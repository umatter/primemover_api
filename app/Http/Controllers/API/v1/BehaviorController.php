<?php

namespace App\Http\Controllers\API\v1;

use App\Behavior;
use App\Http\Resources\v1\BehaviorResource;
use App\Http\Resources\v1\BehaviorResourceCollection;
use Exception;
use Illuminate\Http\Request;

class BehaviorController extends Controller
{

    public function index(Request $request): BehaviorResourceCollection
    {
        $keyword = $request->input('keyword');
        $behaviors = Behavior::with(['job', 'user'])->latest()->get();
        return new BehaviorResourceCollection($behaviors);
    }

    public function create()
    {
        //
        return new BehaviorResourceCollection(new Behavior);
    }

    public function store(Request $request): BehaviorResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $behavior = Behavior::create($data);
        return new BehaviorResource($behavior);
    }


    public function show($id): BehaviorResource
    {
        $behavior = Behavior::with(['user', 'job'])->findOrFail($id);
        return new BehaviorResource($behavior);
    }

    public function edit(Behavior $behavior): BehaviorResource
    {
        return new BehaviorResource($behavior);
    }


    public function update(Request $request, Behavior $behavior): BehaviorResource
    {
        $data = $request->all();
        $behavior->update($data);
        return new BehaviorResource($behavior);
    }

    /**
     * @throws Exception
     */
    public function destroy(Behavior $behavior): BehaviorResourceCollection
    {
        $behavior->delete();
        return new BehaviorResourceCollection(Behavior::all());
    }
}
