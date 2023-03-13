<?php

namespace App\Modules\Experiment\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Http\Service\ExperimentService;
use App\Modules\Experiment\Model\Experiment;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ExperimentController extends Controller
{
    protected $experimentService;

    public function __construct(ExperimentService $experimentService)
    {
        $this->experimentService = $experimentService;
    }

    public function index(): ResourceCollection
    {
        $experiments = $this->experimentService->getData();

        return ResourceCollection::make($experiments);
    }

    public function all(): ResourceCollection
    {
        $experiments = $this->experimentService->all();

        return ResourceCollection::make($experiments);
    }

    /**
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = $this->experimentService->save($request);

        return JsonResource::make($experiment);
    }

    public function show(Experiment $experiment): JsonResource
    {
        return JsonResource::make($this->experimentService->getById($experiment));
    }

    public function edit(Experiment $experiment): JsonResource
    {
        return JsonResource::make($experiment);
    }

    /**
     * @throws Exception
     */
    public function update(ExperimentStoreRequest $request, Experiment $experiment): JsonResource
    {
        return JsonResource::make($this->experimentService->update($request, $experiment));
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): JsonResource
    {
        return JsonResource::make($this->experimentService->delete($experiment));
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): JsonResource
    {
        return JsonResource::make($this->experimentService->forceDelete($experiment));
    }
}
