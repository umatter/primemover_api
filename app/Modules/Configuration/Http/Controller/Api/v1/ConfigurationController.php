<?php

namespace App\Modules\Configuration\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Param\Model\Param;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ConfigurationController extends Controller
{
    public function index(): ResourceCollection
    {
        $configurations = QueryBuilder::for(Configuration::class)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->with(['params', 'preferences'])
            ->latest();

        return ResourceCollection::make($configurations->get())
            ->additional([
                'values' => [
                    'name' => $configurations->pluck('name')->unique(),
                ],
                'count' => $configurations->count(),
            ]);
    }

    public function store(Request $request): JsonResource
    {
        $data['user_id'] = Auth::id() ?? 1;
        $data['name'] = $request->name;
        $data['description'] = $request->description;
        $data['active'] = $request->active;
        $configuration = Configuration::create($data);

        $params = collect($request->params)->first();
        $params['configuration_id'] = $configuration->id;
        $params['user_id'] = Auth::id() ?? 1;

        $newparam = new Param($params);
        $newparam->save();

        return JsonResource::make($configuration);
    }

    public function show(Configuration $configuration): JsonResource
    {
        return JsonResource::make($configuration->load(['crawler', 'params', 'preferences']));
    }

    public function edit(Configuration $configuration): JsonResource
    {
        return JsonResource::make($configuration);
    }

    public function update(Request $request, Configuration $configuration): JsonResource
    {
        $data['user_id'] = Auth::id() ?? 1;
        $data['name'] = $request->name;
        $data['description'] = $request->description;
        $data['active'] = $request->active;
        $configuration->update($data);

        return JsonResource::make($configuration);
    }

    public function destroy(Configuration $configuration): ResourceCollection
    {
        $configuration->delete();

        return ResourceCollection::make(Configuration::all());
    }
}
