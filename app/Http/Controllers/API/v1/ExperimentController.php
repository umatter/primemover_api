<?php

namespace App\Http\Controllers\API\v1;

use App\Experiment;
use App\Preference;
use Illuminate\Http\Request;

use App\Http\Resources\v1\ExperimentResource;
use App\Http\Resources\v1\ExperimentResourceCollection;

class ExperimentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ExperimentResourceCollection
     */
    public function index(Request $request): ExperimentResourceCollection
    {
        $keyword = $request->input('keyword');
        $experiments = Experiment::with('crawlers', 'preferences')->latest()->get();
        return new ExperimentResourceCollection($experiments);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return ExperimentResourceCollection
     */
    public function create()
    {
        //
        return new ExperimentResourceCollection(new Experiment);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return ExperimentResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): ExperimentResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $experiment = Experiment::create([
            "name" => isset($data['name']) ? $data['name'] : 'Value not provided at update!',
            "description" => isset($data['description']) ? $data['description'] : 'Value not provided at update!',
            "user_id" => 1,
            "contact" => isset($data['contact']) ? $data['contact'] : 'Value not provided at update!',
        ]);

        $experiment->save();

        if (isset($request['preferences']) && $request['preferences']) {
            $this->makePreferences($request['preferences'], $experiment);
        }

        return new ExperimentResource($experiment);
    }

    /**
     * Creates Preferences for Configurations
     * @param $data
     * @param $model
     */
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

    /**
     * Show a resource.
     * @param $id
     * @return ExperimentResource
     */
    public function show($id): ExperimentResource
    {
        $experiment = Experiment::with(['user', 'crawlers', 'preferences'])->findOrFail($id);
        return new ExperimentResource($experiment);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Experiment $experiment
     * @return mixed
     */
    public function edit(Experiment $experiment): ExperimentResource
    {
        return new ExperimentResource($experiment);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Experiment $experiment
     * @return ExperimentResource
     */
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
     * Remove the specified resource from storage.
     * @param Experiment $experiment
     * @return ExperimentResourceCollection
     * @throws \Exception
     */
    public function destroy(Experiment $experiment): ExperimentResourceCollection
    {
        $experiment->delete();
        return new ExperimentResourceCollection(Experiment::all());
    }
}
