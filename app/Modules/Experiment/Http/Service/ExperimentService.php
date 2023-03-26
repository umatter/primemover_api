<?php

namespace App\Modules\Experiment\Http\Service;

use App\Facades\Services\PreferenceService;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExperimentService
{
    protected Experiment $experiment;

    public function __construct(Experiment $experiment)
    {
        $this->experiment = $experiment;
    }
    
    public function getData(): LengthAwarePaginator
    {
        $experiments = QueryBuilder::for($this->experiment)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest()
            ->fastPaginate();

        return $experiments;
    }

    public function all(): Collection
    {
        $experiments = $this->experiment->query()
            ->latest()
            ->select(['id', 'name'])
            ->get();

        return $experiments;
    }
    
    public function getById($experiment): Experiment
    {
        return $experiment->load([
            'crawlers.agent',
            'crawlers.proxy',
            'crawlers.configuration',
            'crawlers.configuration.preferences',
            'preferences',
        ]);
    }

    public function save($data): Experiment
    {
        $experiment = $this->experiment->query()->create($data->validated());

        if ($data->getPreferences()) {
            PreferenceService::createManyForModel($data->getPreferences(), $experiment);
        }

        return $experiment;
    }

    public function update($data, Experiment $experiment): Experiment
    {
        $experiment->update($data->validated());

        if ($data->getPreferences()) {
            PreferenceService::createManyForModel($data->getPreferences(), $experiment);
        }

        return $experiment;
    }

    public function delete(Experiment $experiment): array
    {
        $deleted = $experiment->delete();

        return [
            'experiment' => $experiment,
            'deleted' => $deleted,
        ];
    }

    public function forceDelete(Experiment $experiment): array
    {
        $experiment->crawlers()->each(function ($crawler) {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();

        $deleted = $experiment->delete();

        return [
            'experiment' => $experiment,
            'deleted' => $deleted,
        ];
    }
    
}