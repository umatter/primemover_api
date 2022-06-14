<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\v1\AgentResource;
use App\Http\Resources\v1\AgentResourceCollection;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    public function index(): AgentResourceCollection
    {
        $agents = Agent::query()
            ->with('crawler', 'preferences')
            ->latest()
            ->get();

        return AgentResourceCollection::make($agents);
    }

    public function create(): AgentResourceCollection
    {
        return AgentResourceCollection::make(App::make(Agent::class));
    }

    public function store(Request $request): AgentResource
    {
        $data = $request->all();
        $data['user_id'] = Auth::id() ?? 1;

        $agent = Agent::query()->create($data);

        return AgentResource::make($agent);
    }

    public function show($id): AgentResource
    {
        $agent = Agent::query()
            ->with(['user', 'crawler', 'preferences'])
            ->findOr($id, fn () => ['status' => false]);

        return AgentResource::make($agent);
    }

    public function edit(Agent $agent): AgentResource
    {
        return AgentResource::make($agent);
    }

    public function update(Request $request, Agent $agent): AgentResource
    {
        $data = $request->all();
        $agent->update($data);

        return AgentResource::make($agent);
    }

    public function destroy(Agent $agent): AgentResourceCollection
    {
        $agent->delete();

        return AgentResourceCollection::make(Agent::query()->get());
    }
}
