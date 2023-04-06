<?php

namespace App\Modules\Experiment\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Repository\ExperimentRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class ExperimentController extends Controller
{
    public function __construct(protected ExperimentRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $experiments = $this->repository->getData();

        return ResourceCollection::make($experiments);
    }

    public function all(): ResourceCollection
    {
        $experiments = $this->repository->all();

        return ResourceCollection::make($experiments);
    }

    /**
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = $this->repository->save($request);

        return JsonResource::make($experiment);
    }

    public function show(Experiment $experiment): JsonResource
    {
        return JsonResource::make($this->repository->getById($experiment));
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
        return JsonResource::make($this->repository->update($request, $experiment));
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): JsonResource
    {
        return JsonResource::make($this->repository->delete($experiment));
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): JsonResource
    {
        return JsonResource::make($this->repository->forceDelete($experiment));
    }

    public function upload(Request $request): JsonResource
    {
        $crawler = $this->repository->upload($request);

        return JsonResource::make(Crawler::find($crawler->id));
    }

    public function download(Experiment $experiment): Response
    {
        return $this->repository->download($experiment);
    }
}
