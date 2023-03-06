<?php

namespace App\Modules\Agent\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Http\Request\AgentStoreRequest;
use App\Modules\Agent\Model\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AgentController extends Controller
{
    public function index(): ResourceCollection
    {
        $agents = QueryBuilder::for(Agent::class)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->with('preferences')
            ->latest();

        return ResourceCollection::make($agents->get())
            ->additional([
                'values' => [
                    'name' => $agents->pluck('name')->unique(),
                    'identification' => $agents->pluck('identification')->unique(),
                    'multilogin_id' => $agents->pluck('multilogin_id')->unique(),
                    'multilogin_profile' => $agents->pluck('multilogin_profile')->unique(),
                    'location' => $agents->pluck('location')->unique(),
                ],
                'count' => $agents->count(),
            ]);
    }

    public function create(): ResourceCollection
    {
        return ResourceCollection::make(App::make(Agent::class));
    }

    public function store(AgentStoreRequest $request): JsonResource
    {
        return JsonResource::make(
            Agent::query()->create($request->validated())
        );
    }

    public function show($id): JsonResource
    {
        return JsonResource::make(
            Agent::query()
                ->with(['preferences'])
                ->findOr($id, fn () => ['status' => false])
        );
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

    public function destroy(Agent $agent): JsonResource
    {
        return JsonResource::make(['status' => $agent->delete()]);
    }
}
