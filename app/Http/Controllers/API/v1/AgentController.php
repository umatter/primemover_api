<?php

namespace App\Http\Controllers\API\v1;

use App\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Http\Resources\v1\AgentResource;
use App\Http\Resources\v1\AgentResourceCollection;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): AgentResourceCollection
    {
        /*return response()->json([

            'agents' => Agent::latest()->get()

        ], Response::HTTP_OK);*/

        $keyword = $request->input('keyword');
        $agents = Agent::latest()->paginate(5);
        return new AgentResourceCollection($agents);
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
     * @return AgentResourceCollection
     */
    public function store(Request $request): AgentResourceCollection
    {

        $this->validate($request, [
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user()->id;

        $agent = Agent::create($data);
        $agents = Agent::latest()->paginate(5);
        return new AgentResourceCollection($agents, $agent);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Agent $agent
     * @return \Illuminate\Http\Response
     */
    public function show($id): AgentResource
    {
        $agent = Agent::with(['user', 'crawler'])->findOrFail($id);
        return new AgentResource($agent);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Agent $agent
     * @return \Illuminate\Http\Response
     */
    public function edit(Agent $agent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Agent $agent
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Agent $agent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Agent $agent
     * @return \Illuminate\Http\Response
     */
    public function destroy(Agent $agent): AgentResourceCollection
    {
        if ($agent) {
            $agent->delete();
            //return Agent::latest()->get();
            $agents = Agent::latest()->paginate(5);
            return new AgentResourceCollection($agents);
        }
        return false;
    }
}
