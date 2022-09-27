<?php

namespace App\Modules\Experiment\Http\Controller\Api\v1;

use App\Facades\Services\PreferenceService;
use App\Http\Controllers\API\v1\Controller;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Model\Experiment;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExperimentController extends Controller
{
    public function index(): ResourceCollection
    {
        $experiments = QueryBuilder::for(Experiment::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest()
            ->fastPaginate();

        return ResourceCollection::make($experiments);
    }

    public function all(): ResourceCollection
    {
        $experiments = Experiment::query()
            ->latest()
            ->select(['id', 'name'])
            ->get();

        return ResourceCollection::make($experiments);
    }

    /**
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = Experiment::query()->create($request->validated());

        if ($request->getPreferences()) {
            PreferenceService::createManyForModel($request->getPreferences(), $experiment);
        }

        return JsonResource::make($experiment);
    }

    public function show(Experiment $experiment): JsonResource
    {
        return JsonResource::make(
            $experiment->load([
                'crawlers.agent',
                'crawlers.proxy',
                'crawlers.configuration',
                'crawlers.configuration.preferences',
                'preferences',
            ])
        );
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
        $experiment->update($request->validated());

        if ($request->getPreferences()) {
            PreferenceService::createManyForModel($request->getPreferences(), $experiment);
        }

        return JsonResource::make($experiment);
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): JsonResource
    {
        $deleted = $experiment->delete();

        return JsonResource::make([
            'experiment' => $experiment,
            'deleted' => $deleted,
        ]);
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): JsonResource
    {
        $experiment->crawlers()->each(function ($crawler) {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();

        $deleted = $experiment->delete();

        return JsonResource::make([
            'experiment' => $experiment,
            'deleted' => $deleted,
        ]);
    }
}
