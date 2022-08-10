<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    public function index(): ResourceCollection
    {
        $agents = Agent::query()
            ->with('crawler', 'preferences')
            ->latest()
            ->paginate(10);

        return ResourceCollection::make($agents);
    }

    public function create(): ResourceCollection
    {
        return ResourceCollection::make(App::make(Agent::class));
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();
        $data['user_id'] = Auth::id() ?? 1;

        $agent = Agent::query()->create($data);

        return JsonResource::make($agent);
    }

    public function show($id): JsonResource
    {
        $agent = Agent::query()
            ->with(['user', 'crawler', 'preferences'])
            ->findOr($id, fn () => ['status' => false]);

        return JsonResource::make($agent);
    }

    public function edit(Agent $agent): JsonResource
    {
        return JsonResource::make($agent);
    }

    public function update(Request $request, Agent $agent): JsonResource
    {
        $data = $request->all();
        $agent->update($data);

        return JsonResource::make($agent);
    }

    public function destroy(Agent $agent): ResourceCollection
    {
        $agent->delete();

        return ResourceCollection::make(Agent::query()->get());
    }
}
