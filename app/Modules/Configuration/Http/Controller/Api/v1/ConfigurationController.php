<?php

namespace App\Modules\Configuration\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Configuration\Facade\Factory\ConfigurationFactory;
use App\Modules\Configuration\Http\Request\ConfigurationStoreRequest;
use App\Modules\Configuration\Http\Request\ConfigurationUpdateRequest;
use App\Modules\Configuration\Model\Configuration;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
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

    public function store(ConfigurationStoreRequest $request): JsonResource
    {
        $configuration = ConfigurationFactory::create($request->validated());
        $configuration->save();
        // TODO $configuration->fresh()->params()->create($request->getParamsList());

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

    public function update(ConfigurationUpdateRequest $request, Configuration $configuration): JsonResource
    {
        return JsonResource::make([
            'status' => $configuration->update($request->validated()),
            'data' => $configuration->fresh(),
        ]);
    }

    public function destroy(Configuration $configuration): JsonResource
    {
        return JsonResource::make(['status' => $configuration->delete()]);
    }
}
