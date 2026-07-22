<?php

namespace App\Modules\Behavior\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Behavior\Repository\BehaviorRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Behaviors",
 *     description="Behavior management endpoints"
 * )
 */
class BehaviorController extends Controller
{
    public function __construct(protected BehaviorRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *     path="/behaviors",
     *     summary="List all behaviors",
     *     description="Get paginated list of behaviors",
     *     operationId="getBehaviors",
     *     tags={"Behaviors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of behaviors",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="click"),
     *                     @OA\Property(property="description", type="string", example="Click behavior"),
     *                     @OA\Property(property="value", type="string", example="button"),
     *                     @OA\Property(property="job_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        $behaviors = $this->repository->all();

        return ResourceCollection::make($behaviors);
    }

    /**
     * @OA\Post(
     *     path="/behaviors",
     *     summary="Create new behavior",
     *     description="Create a new behavior",
     *     operationId="createBehavior",
     *     tags={"Behaviors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "job_id"},
     *             @OA\Property(property="name", type="string", example="click"),
     *             @OA\Property(property="description", type="string", example="Click behavior"),
     *             @OA\Property(property="value", type="string", example="button"),
     *             @OA\Property(property="job_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Behavior created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResource
    {
        $data = $request->all();

        // The behaviors table requires user_id (NOT NULL FK) and description (NOT NULL). Unlike the
        // other modules this controller never injected them, so a client POST without them 500s.
        $data['user_id'] = $data['user_id'] ?? (Auth::id() ?? 1);
        $data['description'] = $data['description'] ?? '';

        $behavior = $this->repository->save($data);

        return JsonResource::make($behavior);
    }

    /**
     * @OA\Get(
     *     path="/behaviors/{id}",
     *     summary="Get specific behavior",
     *     description="Get details of a specific behavior",
     *     operationId="getBehavior",
     *     tags={"Behaviors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Behavior ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Behavior details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Behavior not found")
     * )
     */
    public function show($id): JsonResource
    {
        $behavior = $this->repository->getById($id);

        return JsonResource::make($behavior);
    }

    public function edit(Behavior $behavior): JsonResource
    {
        return JsonResource::make($behavior);
    }

    /**
     * @OA\Put(
     *     path="/behaviors/{id}",
     *     summary="Update behavior",
     *     description="Update an existing behavior",
     *     operationId="updateBehavior",
     *     tags={"Behaviors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Behavior ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="value", type="string"),
     *             @OA\Property(property="job_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Behavior updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Behavior not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Behavior $behavior): JsonResource
    {
        $behavior = $this->repository->update($request->all(), $behavior);

        return JsonResource::make($behavior);
    }

    /**
     * @OA\Delete(
     *     path="/behaviors/{id}",
     *     summary="Delete behavior",
     *     description="Delete an existing behavior",
     *     operationId="deleteBehavior",
     *     tags={"Behaviors"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Behavior ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Behavior deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Behavior not found")
     * )
     * 
     * @throws Exception
     */
    public function destroy(Behavior $behavior): ResourceCollection
    {
        $this->repository->delete($behavior);

        return ResourceCollection::make(Behavior::all());
    }
}
