<?php

namespace App\Modules\Proxy\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Proxy\Http\Request\ProxyIndexRequest;
use App\Modules\Proxy\Http\Service\ProxyService;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProxyController extends Controller
{
    protected $proxyService;

    public function __construct(ProxyService $proxyService)
    {
        $this->proxyService = $proxyService;
    }
    
    public function index(ProxyIndexRequest $request): ResourceCollection
    {
        return $this->proxyService->getData($request);
    }

    public function store(Request $request): JsonResource
    {
        $proxy = $this->proxyService->save($request->all());

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
        return JsonResource::make($this->proxyService->update($request->all(), $proxy));
    }

    public function destroy(Proxy $proxy): ResourceCollection
    {
        return ResourceCollection::make($this->proxyService->delete($proxy));
    }
}
