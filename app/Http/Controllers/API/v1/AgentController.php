<?php

namespace App\Http\Controllers\API\v1;

use App\Agent;
use App\Crawler;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

use App\Http\Resources\v1\AgentResource;
use App\Http\Resources\v1\AgentResourceCollection;
use mysql_xdevapi\Exception;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AgentResourceCollection
     */
    public function index(Request $request): AgentResourceCollection
    {
        $keyword = $request->input('keyword');
        $agents = Agent::with('crawler', 'preferences')->latest()->get();
        return new AgentResourceCollection($agents);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return AgentResourceCollection
     */
    public function create()
    {
        //
        return new AgentResourceCollection(new Agent);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return AgentResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): AgentResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $agent = Agent::create($data);
        return new AgentResource($agent);
    }

    /**
     * Show a resource.
     * @param $id
     * @return AgentResource
     */
    public function show($id): AgentResource
    {
        $agent = Agent::with(['user', 'crawler', 'preferences'])->findOrFail($id);
        return new AgentResource($agent);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Agent $agent
     * @return mixed
     */
    public function edit(Agent $agent): AgentResource
    {
        return new AgentResource($agent);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Agent $agent
     * @return AgentResource
     */
    public function update(Request $request, Agent $agent): AgentResource
    {
        $data = $request->all();
        $agent->update($data);
        return new AgentResource($agent);
    }

    /**
     * Remove the specified resource from storage.
     * @param Agent $agent
     * @return AgentResourceCollection
     * @throws \Exception
     */
    public function destroy(Agent $agent): AgentResourceCollection
    {
        $agent->delete();
        return new AgentResourceCollection(Agent::all());
    }
}
