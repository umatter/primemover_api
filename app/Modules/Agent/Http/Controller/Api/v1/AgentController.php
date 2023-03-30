<?php

namespace App\Modules\Agent\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Http\Request\AgentStoreRequest;
use App\Modules\Agent\Model\Agent;
use App\Modules\Agent\Repository\AgentRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;

class AgentController extends Controller
{
    public function __construct(protected AgentRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $agents = $this->repository->getData();

        return ResourceCollection::make($agents->get())
            ->additional($this->repository->additional($agents));
    }

    public function create(): ResourceCollection
    {
        return ResourceCollection::make(App::make(Agent::class));
    }

    public function store(AgentStoreRequest $request): JsonResource
    {
        $newAgent = $this->repository->save($request->validated());

        return JsonResource::make($newAgent);
    }

    public function show($id): JsonResource
    {
        return JsonResource::make($this->repository->getById($id));
    }

    public function edit(Agent $agent): JsonResource
    {
        return JsonResource::make($agent);
    }

    public function update(Request $request, Agent $agent): JsonResource
    {
        $agent = $this->repository->update($request->all(), $agent);

        return JsonResource::make($agent);
    }

    public function destroy(Agent $agent): JsonResource
    {
        return JsonResource::make($this->repository->delete($agent));
    }
}
