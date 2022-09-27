<?php

namespace App\Modules\Proxy\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProxyController extends Controller
{
    public function index(): ResourceCollection
    {
        $proxies = QueryBuilder::for(Proxy::class)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest();

        return ResourceCollection::make($proxies->get())
            ->additional([
                'values' => [
                    'name' => $proxies->pluck('name')->unique(),
                    'hostname' => $proxies->pluck('hostname')->unique(),
                    'username' => $proxies->pluck('username')->unique(),
                    'port' => $proxies->pluck('port')->unique(),
                ],
                'count' => $proxies->count(),
            ]);
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();
        $data['user_id'] = Auth::id() ?? 1;

        $proxy = Proxy::query()->create($data);

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
        $data = $request->all();
        $proxy->update($data);

        return JsonResource::make($proxy);
    }

    public function destroy(Proxy $proxy): ResourceCollection
    {
        $proxy->delete();

        return ResourceCollection::make(
            Proxy::with(
                ['user', 'crawler']
            )->latest()->get()
        );
    }
}
