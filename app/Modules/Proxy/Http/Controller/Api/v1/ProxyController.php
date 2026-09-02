<?php

namespace App\Modules\Proxy\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Proxy\Http\Request\ProxyIndexRequest;
use App\Modules\Proxy\Http\Request\ProxyStoreRequest;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Proxy\Repository\ProxyRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Proxies",
 *     description="Proxy management endpoints"
 * )
 */
class ProxyController extends Controller
{
    public function __construct(protected ProxyRepository $repository) {}

    /**
     * @OA\Get(
     *     path="/proxies",
     *     summary="List all proxies",
     *     description="Get list of proxies",
     *     operationId="getProxies",
     *     tags={"Proxies"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="filter[search]",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[recent]",
     *         in="query",
     *         description="Filter recent proxies",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[after]",
     *         in="query",
     *         description="Filter after date",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of proxies",
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
     *                     @OA\Property(property="name", type="string", example="Proxy Name"),
     *                     @OA\Property(property="description", type="string", example="Proxy description"),
     *                     @OA\Property(property="hostname", type="string", example="proxy.example.com"),
     *                     @OA\Property(property="port", type="integer", example=8080),
     *                     @OA\Property(property="username", type="string", example="user"),
     *                     @OA\Property(property="type", type="string", example="http")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(ProxyIndexRequest $request): ResourceCollection
    {
        return $this->repository->all($request);
    }

    /**
     * @OA\Post(
     *     path="/proxies",
     *     summary="Create new proxy",
     *     description="Create a new proxy",
     *     operationId="createProxy",
     *     tags={"Proxies"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="Proxy Name"),
     *             @OA\Property(property="description", type="string", example="Proxy description"),
     *             @OA\Property(property="hostname", type="string", example="proxy.example.com"),
     *             @OA\Property(property="port", type="integer", example=8080),
     *             @OA\Property(property="username", type="string", example="user"),
     *             @OA\Property(property="password", type="string", example="password"),
     *             @OA\Property(property="type", type="string", example="http"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Proxy created successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ProxyStoreRequest $request): JsonResource
    {
        $proxy = $this->repository->save($request->validated());

        return JsonResource::make($proxy);
    }

    /**
     * @OA\Get(
     *     path="/proxies/{id}",
     *     summary="Get specific proxy",
     *     description="Get details of a specific proxy",
     *     operationId="getProxy",
     *     tags={"Proxies"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Proxy ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proxy details",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Proxy not found")
     * )
     */
    public function show(Proxy $proxy): JsonResource
    {
        return JsonResource::make($proxy);
    }

    public function edit(Proxy $proxy): JsonResource
    {
        return JsonResource::make($proxy);
    }

    /**
     * @OA\Put(
     *     path="/proxies/{id}",
     *     summary="Update proxy",
     *     description="Update an existing proxy",
     *     operationId="updateProxy",
     *     tags={"Proxies"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Proxy ID",
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
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="hostname", type="string"),
     *             @OA\Property(property="port", type="integer"),
     *             @OA\Property(property="username", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="active", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proxy updated successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Proxy not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Proxy $proxy): JsonResource
    {
        return JsonResource::make($this->repository->update($request->all(), $proxy));
    }

    /**
     * @OA\Delete(
     *     path="/proxies/{id}",
     *     summary="Delete proxy",
     *     description="Delete an existing proxy",
     *     operationId="deleteProxy",
     *     tags={"Proxies"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Proxy ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Proxy deleted successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Proxy not found")
     * )
     */
    public function destroy(Proxy $proxy): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($proxy));
    }
}
