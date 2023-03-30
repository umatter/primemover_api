<?php

namespace App\Modules\Configuration\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Configuration\Http\Request\ConfigurationStoreRequest;
use App\Modules\Configuration\Http\Request\ConfigurationUpdateRequest;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Configuration\Service\ConfigurationService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ConfigurationController extends Controller
{
    public function __construct(protected ConfigurationService $configurationService)
    {
    }

    public function index(): ResourceCollection
    {
        $configurations = $this->configurationService->getData();

        return ResourceCollection::make($configurations->get())
            ->additional($this->configurationService->additional($configurations));
    }

    public function store(ConfigurationStoreRequest $request): JsonResource
    {
        $configuration = $this->configurationService->save($request->validated());
        // TODO $configuration->fresh()->params()->create($request->getParamsList());

        return JsonResource::make($configuration);
    }

    public function show(Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->configurationService->getById($configuration));
    }

    public function edit(Configuration $configuration): JsonResource
    {
        return JsonResource::make($configuration);
    }

    public function update(ConfigurationUpdateRequest $request, Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->configurationService->update($request->validated(), $configuration));
    }

    public function destroy(Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->configurationService->delete($configuration));
    }
}
