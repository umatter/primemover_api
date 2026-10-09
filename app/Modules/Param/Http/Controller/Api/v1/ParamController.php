<?php

namespace App\Modules\Param\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Param\Http\Request\ParamStoreRequest;
use App\Modules\Param\Http\Request\ParamUpdateRequest;
use App\Modules\Param\Model\Param;
use App\Modules\Param\Repository\ParamRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Params",
 *     description="Parameter management endpoints"
 * )
 */
class ParamController extends Controller
{
    public function __construct(protected ParamRepository $repository) {}

    /**
     * @OA\Get(
     *     path="/params",
     *     summary="List all parameters",
     *     description="Get list of parameters",
     *     operationId="getParams",
     *     tags={"Params"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of parameters",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="pi", type="number", example=-0.5),
     *                     @OA\Property(property="psi", type="number", example=0.08),
     *                     @OA\Property(property="alpha", type="number", example=0.36),
     *                     @OA\Property(property="tau", type="integer", example=1),
     *                     @OA\Property(property="kappa", type="integer", example=1),
     *                     @OA\Property(property="beta", type="number", example=0.003),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        $params = $this->repository->all();

        return ResourceCollection::make($params);
    }

    /**
     * @OA\Post(
     *     path="/params",
     *     summary="Create new parameter",
     *     description="Create a new parameter",
     *     operationId="createParam",
     *     tags={"Params"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="pi", type="number", example=-0.5),
     *             @OA\Property(property="psi", type="number", example=0.08),
     *             @OA\Property(property="alpha", type="number", example=0.36),
     *             @OA\Property(property="tau", type="integer", example=1),
     *             @OA\Property(property="kappa", type="integer", example=1),
     *             @OA\Property(property="beta", type="number", example=0.003),
     *             @OA\Property(property="configuration_id", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Parameter created successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ParamStoreRequest $request): JsonResource
    {
        $param = $this->repository->save($request->payload());

        return JsonResource::make($param);
    }

    /**
     * @OA\Get(
     *     path="/params/{id}",
     *     summary="Get specific parameter",
     *     description="Get details of a specific parameter",
     *     operationId="getParam",
     *     tags={"Params"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Parameter ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Parameter details",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Parameter not found")
     * )
     */
    public function show($id): JsonResource
    {
        $param = $this->repository->getById($id);

        return JsonResource::make($param);
    }

    public function edit(Param $param): JsonResource
    {
        return JsonResource::make($param);
    }

    /**
     * @OA\Put(
     *     path="/params/{id}",
     *     summary="Update parameter",
     *     description="Update an existing parameter",
     *     operationId="updateParam",
     *     tags={"Params"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Parameter ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="pi", type="number"),
     *             @OA\Property(property="psi", type="number"),
     *             @OA\Property(property="alpha", type="number"),
     *             @OA\Property(property="tau", type="integer"),
     *             @OA\Property(property="kappa", type="integer"),
     *             @OA\Property(property="beta", type="number")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Parameter updated successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Parameter not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(ParamUpdateRequest $request, Param $param): JsonResource
    {
        return JsonResource::make($this->repository->update($request->payload(), $param));
    }

    /**
     * @OA\Delete(
     *     path="/params/{id}",
     *     summary="Delete parameter",
     *     description="Delete an existing parameter",
     *     operationId="deleteParam",
     *     tags={"Params"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Parameter ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Parameter deleted successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Parameter not found")
     * )
     */
    public function destroy(Param $param): ResourceCollection
    {
        $this->authorize('delete', $param);

        return ResourceCollection::make($this->repository->delete($param));
    }
}
