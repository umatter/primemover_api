<?php

namespace App\Http\Controllers\API\v1;

use App\Experiment;
use App\Http\Resources\v1\ExperimentResource;
use App\Http\Resources\v1\ExperimentResourceCollection;
use App\Preference;
use Exception;
use Illuminate\Http\Request;

class ExperimentController extends Controller
{
    public function index(Request $request): ExperimentResourceCollection
    {
        $keyword = $request->input('keyword');
        $experiments = Experiment::with([
            'crawlers.agent',
            'crawlers.proxy',
            'crawlers.configuration',
            'preferences'
        ])->latest()->get();
        return new ExperimentResourceCollection($experiments);
    }

    public function create(): ExperimentResourceCollection
    {
        return new ExperimentResourceCollection(new Experiment);
    }

    public function store(Request $request): ExperimentResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $experiment = Experiment::create([
            "name" => $data['name'] ?? 'Value not provided at update!',
            "description" => $data['description'] ?? 'Value not provided at update!',
            "user_id" => 1,
            "contact" => $data['contact'] ?? 'Value not provided at update!',
        ]);

        $experiment->save();

        if (isset($request['preferences']) && $request['preferences']) {
            $this->makePreferences($request['preferences'], $experiment);
        }

        return new ExperimentResource($experiment);
    }

    public function makePreferences($data, $model)
    {
        foreach ($data as $preference) {
            $new = new Preference([
                "user_id" => 1,
                "name" => $preference['name'] ?? 'Value not provided at update!',
                "description" => $preference['description'] ?? 'Value not provided at update!',
                "value" => $preference['value'] ?? 'Value not provided at update!',
            ]);
            $new->preferencable()->associate($model);
            $new->save();
        }
    }

    public function show($id): ExperimentResource
    {
        $experiment = Experiment::with(
            [
                'crawlers.agent',
                'crawlers.proxy',
                'crawlers.configuration',
                'preferences'
            ]
        )->findOrFail($id);
        return new ExperimentResource($experiment);
    }

    public function edit(Experiment $experiment): ExperimentResource
    {
        return new ExperimentResource($experiment);
    }

    public function update(Request $request, Experiment $experiment): ExperimentResource
    {
        $data = $request->all();
        $experiment->update($data);

        if (isset($request['preferences']) && $request['preferences']) {
            $this->makePreferences($request['preferences'], $experiment);
        }
        return new ExperimentResource($experiment);
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): ExperimentResourceCollection
    {
        $experiment->delete();
        return new ExperimentResourceCollection(Experiment::all());
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): ExperimentResourceCollection
    {
        $experiment->crawlers()->each(function ($crawler) {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();
        $experiment->delete();

        return new ExperimentResourceCollection(
            Experiment::with(
                'crawlers.agent',
                'crawlers.proxy',
                'crawlers.configuration',
                'preferences'
            )->latest()->get()
        );
    }
}
