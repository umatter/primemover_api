<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Proxy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class ProxyController extends Controller
{
    public function index(): ResourceCollection
    {
        $proxies = Proxy::with(
            [
                'user',
                'crawler',
            ]
        )->latest()->get();

        return ResourceCollection::make($proxies);
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
