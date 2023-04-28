<?php

namespace App\Modules\Experiment\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Facade\ExperimentRepository;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Http\Request\ExperimentUploadRequest;
use App\Modules\Experiment\Model\Experiment;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class ExperimentController extends Controller
{
    public function index(): ResourceCollection
    {
        $experiments = ExperimentRepository::getData();

        return ResourceCollection::make($experiments->paginate());
    }

    public function all(): ResourceCollection
    {
        $experiments = ExperimentRepository::all();

        return ResourceCollection::make($experiments);
    }

    /**
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = ExperimentRepository::save($request);

        return JsonResource::make($experiment);
    }

    public function show(Experiment $experiment): JsonResource
    {
        return JsonResource::make(ExperimentRepository::getById($experiment));
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
        return JsonResource::make(ExperimentRepository::update($request, $experiment));
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): JsonResource
    {
        return JsonResource::make([
            'status' => ExperimentRepository::delete($experiment),
        ]);
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): JsonResource
    {
        return JsonResource::make([
            'status' => ExperimentRepository::forceDelete($experiment),
        ]);
    }

    public function upload(ExperimentUploadRequest $request, Experiment $experiment): JsonResource
    {
        ExperimentRepository::upload($request, $experiment);

        return JsonResource::make([
            'crawlers' => Crawler::query()
                ->where('updated', 1)
                ->with(['proxy', 'agent.preferences', 'configuration.preferences', 'queuesNew'])
                ->get(),
        ]);

    }

    public function download(Experiment $experiment): Response
    {
        return ExperimentRepository::download($experiment);
    }
}
