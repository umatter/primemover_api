<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Param;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ParamController extends Controller
{
    public function index(): ResourceCollection
    {
        $params = Param::with(
            [
                'user',
                'configuration',
            ]
        )->latest()->get();

        return ResourceCollection::make($params);
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $param = Param::query()->create($data);

        return JsonResource::make($param);
    }

    public function show($id): JsonResource
    {
        $param = Param::with(['user'])->findOrFail($id);

        return JsonResource::make($param);
    }

    public function edit(Param $param): JsonResource
    {
        return JsonResource::make($param);
    }

    public function update(Request $request, Param $param): JsonResource
    {
        $data = $request->all();
        $param->update($data);

        return JsonResource::make($param);
    }

    public function destroy(Param $param): ResourceCollection
    {
        $param->delete();

        return ResourceCollection::make(
            Param::with(
                ['user', 'configuration']
            )->latest()->get()
        );
    }
}
