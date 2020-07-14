<?php

namespace App\Http\Controllers\API\v1;

use App\Behavior;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Resources\v1\BehaviorResource;
use App\Http\Resources\v1\BehaviorResourceCollection;

class BehaviorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): BehaviorResourceCollection
    {
        /*return response()->json([

            'agents' => Behavior::latest()->get()

        ], Response::HTTP_OK);*/

        $keyword = $request->input('keyword');
        $behaviors = Behavior::latest()->paginate(5);
        return new BehaviorResourceCollection($behaviors);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return BehaviorResourceCollection
     */
    public function store(Request $request): BehaviorResourceCollection
    {

        $this->validate($request, [
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user()->id;

        $behavior = Behavior::create($data);
        $behaviors = Behavior::latest()->paginate(5);
        return new BehaviorResourceCollection($behaviors, $behavior);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Behavior $behavior
     * @return \Illuminate\Http\Response
     */
    public function show($id): BehaviorResource
    {
        $behavior = Behavior::with(['job'])->findOrFail($id);
        return new BehaviorResource($behavior);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Behavior $behavior
     * @return \Illuminate\Http\Response
     */
    public function edit(Behavior $behavior)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Behavior $behavior
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Behavior $behavior)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Behavior $behavior
     * @return \Illuminate\Http\Response
     */
    public function destroy(Behavior $behavior): BehaviorResourceCollection
    {
        if ($behavior) {
            $behavior->delete();
            //return Behavior::latest()->get();
            $behaviors = Behavior::latest()->paginate(5);
            return new BehaviorResourceCollection($behaviors);
        }
        return false;
    }
}
