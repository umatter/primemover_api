<?php

namespace App\Modules\Agent\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Http\Request\AgentStoreRequest;
use App\Modules\Agent\Model\Agent;
use App\Modules\Agent\Service\AgentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;

class AgentController extends Controller
{
    public function __construct(protected AgentService $agentService)
    {
    }

    public function index(): ResourceCollection
    {
        $agents = $this->agentService->getData();

        return ResourceCollection::make($agents->get())
            ->additional($this->agentService->additional($agents));
    }

    public function create(): ResourceCollection
    {
        return ResourceCollection::make(App::make(Agent::class));
    }

    public function store(AgentStoreRequest $request): JsonResource
    {
        $newAgent = $this->agentService->save($request->validated());

        return JsonResource::make($newAgent);
    }

    public function show($id): JsonResource
    {
        return JsonResource::make($this->agentService->getById($id));
    }

    public function edit(Agent $agent): JsonResource
    {
        return JsonResource::make($agent);
    }

    public function update(Request $request, Agent $agent): JsonResource
    {
        $agent = $this->agentService->update($request->all(), $agent);

        return JsonResource::make($agent);
    }

    public function destroy(Agent $agent): JsonResource
    {
        return JsonResource::make($this->agentService->delete($agent));
    }
}
