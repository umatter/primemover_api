<?php

namespace App\Modules\Agent\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Http\Request\RunnerAgentUpdateRequest;
use App\Modules\Agent\Model\Agent;
use App\Modules\Agent\Repository\AgentRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class RunnerAgentController extends Controller
{
    public function __construct(protected AgentRepository $repository)
    {
    }

    public function update(RunnerAgentUpdateRequest $request, Agent $agent): JsonResource
    {
        $agent = $this->repository->update($request->validated(), $agent);

        return JsonResource::make($agent);
    }
}
