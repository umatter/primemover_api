<?php

namespace App\Http\Controllers\API\v1;

use App\Behavior;
use Exception;
use Illuminate\Http\Request;
use App\Http\Resources\v1\BehaviorResource;
use App\Http\Resources\v1\BehaviorResourceCollection;
use Illuminate\Validation\ValidationException;

class BehaviorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return BehaviorResourceCollection
     */
    public function index(Request $request): BehaviorResourceCollection
    {
        $keyword = $request->input('keyword');
        $behaviors = Behavior::with(['job', 'user'])->latest()->get();
        return new BehaviorResourceCollection($behaviors);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return BehaviorResourceCollection
     */
    public function create()
    {
        //
        return new BehaviorResourceCollection(new Behavior);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return BehaviorResource
     * @throws ValidationException
     */
    public function store(Request $request): BehaviorResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $behavior = Behavior::create($data);
        return new BehaviorResource($behavior);
    }

    /**
     * Show a resource.
     * @param $id
     * @return BehaviorResource
     */
    public function show($id): BehaviorResource
    {
        $behavior = Behavior::with(['user', 'job'])->findOrFail($id);
        return new BehaviorResource($behavior);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Behavior $behavior
     * @return mixed
     */
    public function edit(Behavior $behavior): BehaviorResource
    {
        return new BehaviorResource($behavior);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Behavior $behavior
     * @return BehaviorResource
     */
    public function update(Request $request, Behavior $behavior): BehaviorResource
    {
        $data = $request->all();
        $behavior->update($data);
        return new BehaviorResource($behavior);
    }

    /**
     * Remove the specified resource from storage.
     * @param Behavior $behavior
     * @return BehaviorResourceCollection
     * @throws Exception
     */
    public function destroy(Behavior $behavior): BehaviorResourceCollection
    {
        $behavior->delete();
        return new BehaviorResourceCollection(Behavior::all());
    }
}
