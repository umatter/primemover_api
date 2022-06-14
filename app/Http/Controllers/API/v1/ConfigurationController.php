<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\v1\ConfigurationResource;
use App\Http\Resources\v1\ConfigurationResourceCollection;
use App\Models\Configuration;
use App\Models\Param;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConfigurationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ConfigurationResourceCollection
     */
    public function index(Request $request): ConfigurationResourceCollection
    {
        $keyword = $request->input('keyword');
        $configurations = Configuration::with(['crawler', 'params', 'preferences'])->latest()->get();
        return new ConfigurationResourceCollection($configurations);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return ConfigurationResourceCollection
     */
    public function create()
    {
        //
        return new ConfigurationResourceCollection(new Configuration);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return ConfigurationResource
     * @throws ValidationException
     */
    public function store(Request $request): ConfigurationResource
    {
        //$data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        $data['name'] = $request->name;
        $data['description'] = $request->description;
        $data['active'] = $request->active;
        $configuration = Configuration::create($data);

        $params = collect($request->params)->first();
        $params['configuration_id'] = $configuration->id;
        $params['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $newparam = new Param($params);
        $newparam->save();

        return new ConfigurationResource($configuration);
    }

    /**
     * Show a resource.
     * @param $id
     * @return ConfigurationResource
     */
    public function show($id): ConfigurationResource
    {
        $configuration = Configuration::with(['crawler', 'params', 'preferences'])->findOrFail($id);
        return new ConfigurationResource($configuration);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Configuration $configuration
     * @return mixed
     */
    public function edit(Configuration $configuration): ConfigurationResource
    {
        return new ConfigurationResource($configuration);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param \App\Models\Configuration $configuration
     * @return ConfigurationResource
     */
    public function update(Request $request, Configuration $configuration): ConfigurationResource
    {
        //$data = $request->all();
        //$configuration->update($data);
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        $data['name'] = $request->name;
        $data['description'] = $request->description;
        $data['active'] = $request->active;
        $configuration->update($data);

        return new ConfigurationResource($configuration);
    }

    /**
     * Remove the specified resource from storage.
     * @param Configuration $configuration
     * @return ConfigurationResourceCollection
     * @throws Exception
     */
    public function destroy(Configuration $configuration): ConfigurationResourceCollection
    {
        $configuration->delete();
        return new ConfigurationResourceCollection(Configuration::all());
    }
}
