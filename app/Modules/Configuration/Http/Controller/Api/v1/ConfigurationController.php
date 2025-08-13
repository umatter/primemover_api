<?php

namespace App\Modules\Configuration\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Configuration\Http\Request\ConfigurationStoreRequest;
use App\Modules\Configuration\Http\Request\ConfigurationUpdateRequest;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Configuration\Repository\ConfigurationRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Configurations",
 *     description="Configuration management endpoints"
 * )
 */
class ConfigurationController extends Controller
{
    public function __construct(protected ConfigurationRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/configurations",
     *     summary="List all configurations",
     *     description="Get paginated list of configurations",
     *     operationId="getConfigurations",
     *     tags={"Configurations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="filter[search]",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string", example="config")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of configurations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Configuration Name"),
     *                     @OA\Property(property="description", type="string", example="Configuration description"),
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
        $configurations = $this->repository->all();

        return ResourceCollection::make($configurations->paginate())
            ->additional($this->repository->additional($configurations));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/configurations",
     *     summary="Create new configuration",
     *     description="Create a new configuration",
     *     operationId="createConfiguration",
     *     tags={"Configurations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Configuration Name"),
     *             @OA\Property(property="description", type="string", example="Configuration description"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Configuration created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ConfigurationStoreRequest $request): JsonResource
    {
        $configuration = $this->repository->save($request->validated());
        // TODO $configuration->fresh()->params()->create($request->getParamsList());

        return JsonResource::make($configuration);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/configurations/{id}",
     *     summary="Get specific configuration",
     *     description="Get details of a specific configuration",
     *     operationId="getConfiguration",
     *     tags={"Configurations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Configuration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Configuration not found")
     * )
     */
    public function show(Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->repository->getById($configuration));
    }

    public function edit(Configuration $configuration): JsonResource
    {
        return JsonResource::make($configuration);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/configurations/{id}",
     *     summary="Update configuration",
     *     description="Update an existing configuration",
     *     operationId="updateConfiguration",
     *     tags={"Configurations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Configuration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="active", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Configuration not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(ConfigurationUpdateRequest $request, Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $configuration));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/configurations/{id}",
     *     summary="Delete configuration",
     *     description="Delete an existing configuration",
     *     operationId="deleteConfiguration",
     *     tags={"Configurations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Configuration ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration deleted successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Configuration not found")
     * )
     */
    public function destroy(Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->repository->delete($configuration));
    }
}
