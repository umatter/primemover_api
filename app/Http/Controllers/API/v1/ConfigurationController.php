<?php

namespace App\Http\Controllers\API\v1;

use App\Configuration;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


use App\Http\Resources\v1\ConfigurationResource;
use App\Http\Resources\v1\ConfigurationResourceCollection;

class ConfigurationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request): ConfigurationResourceCollection
    {
        $keyword = $request->input('keyword');
        $configurations = Configuration::with('crawler')->latest()->get();
        return new ConfigurationResourceCollection($configurations);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $configuration = Configuration::create($data);
        return $configuration;
    }

    /**
     * Display the specified resource.
     *
     * @param Configuration $configuration
     * @return Response
     */
    public function show($id): ConfigurationResource
    {
        $configuration = Configuration::with(['crawler'])->findOrFail($id);
        return new ConfigurationResource($configuration);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Configuration $configuration
     * @return Response
     */
    public function edit(Configuration $configuration)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Configuration $configuration
     * @return Response
     */
    public function update(Request $request, Configuration $configuration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Configuration $configuration
     * @return Response
     */
    public function destroy(Configuration $configuration)
    {
        $configuration->delete();
        return Configuration::latest()->get();
    }
}
