<?php

namespace App\Modules\Agent\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Http\Request\AgentStoreRequest;
use App\Modules\Agent\Http\Request\AgentUpdateRequest;
use App\Modules\Agent\Model\Agent;
use App\Modules\Agent\Repository\AgentRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;

/**
 * @OA\Schema(
 *     schema="Agent",
 *     type="object",
 *     title="Agent",
 *     description="Browser agent configuration",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Chrome Agent"),
 *     @OA\Property(property="description", type="string", example="Chrome browser agent"),
 *     @OA\Property(property="identification", type="string", enum={"chrome","firefox","multilogin","mlx"}, example="chrome"),
 *     @OA\Property(property="location", type="string", example="US-CO-COLORADO_SPRINGS"),
 *     @OA\Property(property="multilogin_id", type="string", example="ml_123"),
 *     @OA\Property(property="multilogin_profile", type="object", example={"name":"Profile","os":"win"}),
 *     @OA\Property(property="active", type="boolean", example=true),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class AgentController extends Controller
{
    public function __construct(protected AgentRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *     path="/agents",
     *     summary="List all agents",
     *     description="Get paginated list of browser agents",
     *     tags={"Agents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of agents",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Agent")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        $agents = $this->repository->all();

        return ResourceCollection::make($agents->get())
            ->additional($this->repository->additional($agents));
    }

    public function create(): ResourceCollection
    {
        return ResourceCollection::make(App::make(Agent::class));
    }

    /**
     * @OA\Post(
     *     path="/agents",
     *     summary="Create new agent",
     *     description="Create a new browser agent configuration",
     *     tags={"Agents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","description","identification","location"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Chrome Agent"),
     *             @OA\Property(property="description", type="string", maxLength=255, example="Chrome browser agent"),
     *             @OA\Property(property="identification", type="string", enum={"chrome","firefox","multilogin","mlx"}, example="chrome"),
     *             @OA\Property(property="location", type="string", example="US-CO-COLORADO_SPRINGS"),
     *             @OA\Property(property="multilogin_id", type="string", example="ml_123"),
     *             @OA\Property(property="multilogin_profile", type="object", example={"name":"Profile","os":"win"}),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Agent created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Agent")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(AgentStoreRequest $request): JsonResource
    {
        $newAgent = $this->repository->save($request->validated());

        return JsonResource::make($newAgent);
    }

    /**
     * @OA\Get(
     *     path="/agents/{id}",
     *     summary="Get specific agent",
     *     description="Get details of a specific agent",
     *     tags={"Agents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Agent ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Agent details",
     *         @OA\JsonContent(ref="#/components/schemas/Agent")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Agent not found")
     * )
     */
    public function show(int $id): JsonResource
    {
        return JsonResource::make($this->repository->getById($id));
    }

    public function edit(int $id): JsonResource
    {
        return JsonResource::make($this->repository->getById($id));
    }

    /**
     * @OA\Put(
     *     path="/agents/{id}",
     *     summary="Update agent",
     *     description="Update an existing agent",
     *     tags={"Agents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Agent ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string", maxLength=255),
     *             @OA\Property(property="identification", type="string", enum={"chrome","firefox","multilogin","mlx"}),
     *             @OA\Property(property="location", type="string"),
     *             @OA\Property(property="active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Agent updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Agent")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Agent not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(AgentUpdateRequest $request, Agent $agent): JsonResource
    {
        $agent = $this->repository->update($request->validated(), $agent);

        return JsonResource::make($agent);
    }

    /**
     * @OA\Delete(
     *     path="/agents/{id}",
     *     summary="Delete agent",
     *     description="Delete an existing agent",
     *     tags={"Agents"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Agent ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Agent deleted successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Agent not found")
     * )
     */
    public function destroy(Agent $agent): JsonResource
    {
        return JsonResource::make($this->repository->delete($agent));
    }
}
