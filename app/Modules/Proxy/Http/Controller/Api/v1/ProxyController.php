<?php

namespace App\Modules\Proxy\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Proxy\Http\Request\ProxyIndexRequest;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Proxy\Repository\ProxyRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProxyController extends Controller
{
    public function __construct(protected ProxyRepository $repository)
    {
    }

    public function index(ProxyIndexRequest $request): ResourceCollection
    {
        return $this->repository->getData($request);
    }

    public function store(Request $request): JsonResource
    {
        $proxy = $this->repository->save($request->all());

        return JsonResource::make($proxy);
    }

    public function show(Proxy $proxy): JsonResource
    {
        return JsonResource::make($proxy);
    }

    public function edit(Proxy $proxy): JsonResource
    {
        return JsonResource::make($proxy);
    }

    public function update(Request $request, Proxy $proxy): JsonResource
    {
        return JsonResource::make($this->repository->update($request->all(), $proxy));
    }

    public function destroy(Proxy $proxy): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($proxy));
    }
}
