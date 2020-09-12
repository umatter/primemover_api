<?php

namespace App\Http\Controllers\API\v1;

use App\Outlet;
use Illuminate\Http\Request;

use App\Http\Resources\v1\OutletResource;
use App\Http\Resources\v1\OutletResourceCollection;

class OutletController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return OutletResourceCollection
     */
    public function index(Request $request): OutletResourceCollection
    {

        $keyword = $request->input('keyword');
        $outlets = Outlet::latest()->get();
        return new OutletResourceCollection($outlets);
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
     * @return OutletResourceCollection
     */
    public function store(Request $request): OutletResourceCollection
    {

        $this->validate($request, [
            //'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $outlet = Outlet::create($data);
        $outlets = Outlet::latest()->paginate(5);
        return new OutletResourceCollection($outlets, $outlet);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Outlet $outlet
     * @return \Illuminate\Http\Response
     */
    public function show($id): OutletResource
    {
        $outlet = Outlet::findOrFail($id);
        return new OutletResource($outlet);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Outlet $outlet
     * @return \Illuminate\Http\Response
     */
    public function edit(Outlet $outlet)
    {
        //
        return;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Outlet $outlet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Outlet $outlet)
    {
        //
        return;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Outlet $outlet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Outlet $outlet): OutletResourceCollection
    {
        if ($outlet) {
            $outlet->delete();
            //return Outlet::latest()->get();
            $outlets = Outlet::latest()->paginate(5);
            return new OutletResourceCollection($outlets);
        }
        return false;
    }
}
