<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\v1\OutletResource;
use App\Http\Resources\v1\OutletResourceCollection;
use App\Models\Outlet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        $outlets = Outlet::with(['user'])->latest()->get();
        return new OutletResourceCollection($outlets);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return OutletResourceCollection
     */
    public function create()
    {
        //
        return new OutletResourceCollection(new Outlet);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return OutletResource
     * @throws ValidationException
     */
    public function store(Request $request): OutletResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $outlet = Outlet::create($data);
        return new OutletResource($outlet);
    }

    /**
     * Show a resource.
     * @param $id
     * @return OutletResource
     */
    public function show($id): OutletResource
    {
        $outlet = Outlet::with(['user'])->findOrFail($id);
        return new OutletResource($outlet);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Outlet $outlet
     * @return mixed
     */
    public function edit(Outlet $outlet): OutletResource
    {
        return new OutletResource($outlet);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param \App\Models\Outlet $outlet
     * @return OutletResource
     */
    public function update(Request $request, Outlet $outlet): OutletResource
    {
        $data = $request->all();
        $outlet->update($data);
        return new OutletResource($outlet);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Outlet $outlet
     * @return OutletResourceCollection
     * @throws Exception
     */
    public function destroy(Outlet $outlet): OutletResourceCollection
    {
        $outlet->delete();
        return new OutletResourceCollection(
            Outlet::with(
                ['user']
            )->latest()->get());
    }
}
