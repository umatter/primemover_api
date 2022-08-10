<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class OutletController extends Controller
{
    public function index(): ResourceCollection
    {
        $outlets = Outlet::with(['user'])->latest()->get();

        return ResourceCollection::make($outlets);
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();
        $data['user_id'] = Auth::id() ?? 1;

        $outlet = Outlet::query()->create($data);

        return JsonResource::make($outlet);
    }

    public function show($id): JsonResource
    {
        $outlet = Outlet::with(['user'])->findOrFail($id);

        return JsonResource::make($outlet);
    }

    public function edit(Outlet $outlet): JsonResource
    {
        return JsonResource::make($outlet);
    }

    public function update(Request $request, Outlet $outlet): JsonResource
    {
        $data = $request->all();
        $outlet->update($data);

        return JsonResource::make($outlet);
    }

    public function destroy(Outlet $outlet): ResourceCollection
    {
        $outlet->delete();

        return ResourceCollection::make(
            Outlet::with(
                ['user']
            )->latest()->get()
        );
    }
}
