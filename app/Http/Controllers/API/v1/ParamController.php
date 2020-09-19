<?php

namespace App\Http\Controllers\API\v1;

use App\Param;
use Exception;
use Illuminate\Http\Request;
use App\Http\Resources\v1\ParamResource;
use App\Http\Resources\v1\ParamResourceCollection;
use Illuminate\Validation\ValidationException;

class ParamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ParamResourceCollection
     */
    public function index(Request $request): ParamResourceCollection
    {
        $keyword = $request->input('keyword');
        $params = Param::with(
            [
                'user',
                'configuration'
            ]
        )->latest()->get();
        return new ParamResourceCollection($params);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return ParamResourceCollection
     */
    public function create()
    {
        //
        return new ParamResourceCollection(new Param);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return ParamResource
     * @throws ValidationException
     */
    public function store(Request $request): ParamResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $param = Param::create($data);
        return new ParamResource($param);
    }

    /**
     * Show a resource.
     * @param $id
     * @return ParamResource
     */
    public function show($id): ParamResource
    {
        $param = Param::with(['user'])->findOrFail($id);
        return new ParamResource($param);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Param $param
     * @return mixed
     */
    public function edit(Param $param): ParamResource
    {
        return new ParamResource($param);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Param $param
     * @return ParamResource
     */
    public function update(Request $request, Param $param): ParamResource
    {
        $data = $request->all();
        $param->update($data);
        return new ParamResource($param);
    }

    /**
     * Remove the specified resource from storage.
     * @param Param $param
     * @return ParamResourceCollection
     * @throws Exception
     */
    public function destroy(Param $param): ParamResourceCollection
    {
        $param->delete();
        return new ParamResourceCollection(
            Param::with(
                ['user', 'configuration']
            )->latest()->get());
    }
}
