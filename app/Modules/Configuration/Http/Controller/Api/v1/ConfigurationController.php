<?php

namespace App\Modules\Configuration\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Configuration\Http\Request\ConfigurationStoreRequest;
use App\Modules\Configuration\Http\Request\ConfigurationUpdateRequest;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Configuration\Repository\ConfigurationRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ConfigurationController extends Controller
{
    public function __construct(protected ConfigurationRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $configurations = $this->repository->getData();

        return ResourceCollection::make($configurations->paginate())
            ->additional($this->repository->additional($configurations));
    }

    public function store(ConfigurationStoreRequest $request): JsonResource
    {
        $configuration = $this->repository->save($request->validated());
        // TODO $configuration->fresh()->params()->create($request->getParamsList());

        return JsonResource::make($configuration);
    }

    public function show(Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->repository->getById($configuration));
    }

    public function edit(Configuration $configuration): JsonResource
    {
        return JsonResource::make($configuration);
    }

    public function update(ConfigurationUpdateRequest $request, Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $configuration));
    }

    public function destroy(Configuration $configuration): JsonResource
    {
        return JsonResource::make($this->repository->delete($configuration));
    }
}
