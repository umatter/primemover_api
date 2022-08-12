<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Experiment;
use App\Models\Preference;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExperimentController extends Controller
{
    public function index(): ResourceCollection
    {
        $experiments = QueryBuilder::for(Experiment::class)
            ->whereHas('crawlers')
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest()
            ->fastPaginate();

        return ResourceCollection::make($experiments);
    }

    public function create(): ResourceCollection
    {
        return ResourceCollection::make(new Experiment());
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();

        $experiment = Experiment::create([
            'name' => $data['name'] ?? 'Value not provided at update!',
            'description' => $data['description'] ?? 'Value not provided at update!',
            'user_id' => Auth::id() ?? 1,
            'contact' => $data['contact'] ?? 'Value not provided at update!',
        ]);

        $experiment->save();

        if (isset($request['preferences']) && $request['preferences']) {
            $this->makePreferences($request['preferences'], $experiment);
        }

        return JsonResource::make($experiment);
    }

    public function makePreferences($data, $model)
    {
        foreach ($data as $preference) {
            $new = new Preference([
                'user_id' => Auth::id() ?? 1,
                'name' => $preference['name'] ?? 'Value not provided at update!',
                'description' => $preference['description'] ?? 'Value not provided at update!',
                'value' => $preference['value'] ?? 'Value not provided at update!',
            ]);
            $new->preferencable()->associate($model);
            $new->save();
        }
    }

    public function show(Experiment $experiment): JsonResource
    {
        return JsonResource::make($experiment->with([
            'crawlers.agent',
            'crawlers.proxy',
            'crawlers.configuration',
            'crawlers.configuration.preferences',
            'preferences',
        ]));
    }

    public function edit(Experiment $experiment): JsonResource
    {
        return JsonResource::make($experiment);
    }

    public function update(Request $request, Experiment $experiment): JsonResource
    {
        $data = $request->all();
        $experiment->update($data);

        if (isset($request['preferences']) && $request['preferences']) {
            $this->makePreferences($request['preferences'], $experiment);
        }

        return JsonResource::make($experiment);
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): ResourceCollection
    {
        $experiment->delete();

        return ResourceCollection::make(Experiment::all());
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): ResourceCollection
    {
        $experiment->crawlers()->each(function ($crawler) {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();
        $experiment->delete();

        return ResourceCollection::make(
            Experiment::with(
                'crawlers.agent',
                'crawlers.proxy',
                'crawlers.configuration',
                'crawlers.configuration.preferences',
                'preferences'
            )->latest()->get()
        );
    }
}
