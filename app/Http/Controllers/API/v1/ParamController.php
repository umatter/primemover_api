<?php

namespace App\Http\Controllers\API\v1;

use App\Param;
use Illuminate\Http\Request;

use App\Http\Resources\v1\ParamResource;
use App\Http\Resources\v1\ParamResourceCollection;

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
        $params = Param::latest()->get();
        return new ParamResourceCollection($params);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return ParamResourceCollection
     */
    public function store(Request $request): ParamResourceCollection
    {

        $this->validate($request, [
            //'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $param = Param::create($data);
        $params = Param::latest()->paginate(5);
        return new ParamResourceCollection($params, $param);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Param $param
     * @return \Illuminate\Http\Response
     */
    public function show($id): ParamResource
    {
        $param = Param::findOrFail($id);
        return new ParamResource($param);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Param $param
     * @return \Illuminate\Http\Response
     */
    public function edit(Param $param)
    {
        //
        return;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Param $param
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Param $param)
    {
        //
        return;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Param $param
     * @return \Illuminate\Http\Response
     */
    public function destroy(Param $param): ParamResourceCollection
    {
        if ($param) {
            $param->delete();
            //return Param::latest()->get();
            $params = Param::latest()->paginate(5);
            return new ParamResourceCollection($params);
        }
        return false;
    }
}
