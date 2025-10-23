<?php

namespace App\Modules\Outlet\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Outlet\Model\Outlet;
use App\Modules\Outlet\Repository\OutletRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Outlets",
 *     description="Media outlet management endpoints"
 * )
 */
class OutletController extends Controller
{
    public function __construct(protected OutletRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *     path="/outlets",
     *     summary="List all outlets",
     *     description="Get list of media outlets",
     *     operationId="getOutlets",
     *     tags={"Outlets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of outlets",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Media Outlet"),
     *                     @OA\Property(property="url", type="string", example="https://example.com"),
     *                     @OA\Property(property="domain", type="string", example="example.com"),
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
        $outlets = $this->repository->all();

        return ResourceCollection::make($outlets);
    }

    /**
     * @OA\Post(
     *     path="/outlets",
     *     summary="Create new outlet",
     *     description="Create a new media outlet",
     *     operationId="createOutlet",
     *     tags={"Outlets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "url"},
     *             @OA\Property(property="name", type="string", example="Media Outlet"),
     *             @OA\Property(property="url", type="string", example="https://example.com"),
     *             @OA\Property(property="domain", type="string", example="example.com"),
     *             @OA\Property(property="description", type="string", example="Outlet description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Outlet created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResource
    {
        $outlet = $this->repository->save($request->all());

        return JsonResource::make($outlet);
    }

    /**
     * @OA\Get(
     *     path="/outlets/{id}",
     *     summary="Get specific outlet",
     *     description="Get details of a specific outlet",
     *     operationId="getOutlet",
     *     tags={"Outlets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Outlet ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Outlet details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Outlet not found")
     * )
     */
    public function show($id): JsonResource
    {
        $outlet = $this->repository->getById($id);

        return JsonResource::make($outlet);
    }

    public function edit(Outlet $outlet): JsonResource
    {
        return JsonResource::make($outlet);
    }

    /**
     * @OA\Put(
     *     path="/outlets/{id}",
     *     summary="Update outlet",
     *     description="Update an existing outlet",
     *     operationId="updateOutlet",
     *     tags={"Outlets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Outlet ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="url", type="string"),
     *             @OA\Property(property="domain", type="string"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Outlet updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Outlet not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Outlet $outlet): JsonResource
    {
        return JsonResource::make($this->repository->update($request->all(), $outlet));
    }

    /**
     * @OA\Delete(
     *     path="/outlets/{id}",
     *     summary="Delete outlet",
     *     description="Delete an existing outlet",
     *     operationId="deleteOutlet",
     *     tags={"Outlets"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Outlet ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Outlet deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Outlet not found")
     * )
     */
    public function destroy(Outlet $outlet): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($outlet));
    }
}
