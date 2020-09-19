<?php

namespace App\Http\Controllers\API\v1;

use App\Proxy;
use Exception;
use Illuminate\Http\Request;
use App\Http\Resources\v1\ProxyResource;
use App\Http\Resources\v1\ProxyResourceCollection;
use Illuminate\Validation\ValidationException;

class ProxyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ProxyResourceCollection
     */
    public function index(Request $request): ProxyResourceCollection
    {
        $keyword = $request->input('keyword');
        $proxies = Proxy::with(
            [
                'user',
                'crawler'
            ]
        )->latest()->get();
        return new ProxyResourceCollection($proxies);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return ProxyResourceCollection
     */
    public function create()
    {
        //
        return new ProxyResourceCollection(new Proxy);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return ProxyResource
     * @throws ValidationException
     */
    public function store(Request $request): ProxyResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $proxy = Proxy::create($data);
        return new ProxyResource($proxy);
    }

    /**
     * Show a resource.
     * @param $id
     * @return ProxyResource
     */
    public function show($id): ProxyResource
    {
        $proxy = Proxy::with(['user'])->findOrFail($id);
        return new ProxyResource($proxy);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Proxy $proxy
     * @return mixed
     */
    public function edit(Proxy $proxy): ProxyResource
    {
        return new ProxyResource($proxy);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Proxy $proxy
     * @return ProxyResource
     */
    public function update(Request $request, Proxy $proxy): ProxyResource
    {
        $data = $request->all();
        $proxy->update($data);
        return new ProxyResource($proxy);
    }

    /**
     * Remove the specified resource from storage.
     * @param Proxy $proxy
     * @return ProxyResourceCollection
     * @throws Exception
     */
    public function destroy(Proxy $proxy): ProxyResourceCollection
    {
        $proxy->delete();
        return new ProxyResourceCollection(
            Proxy::with(
                ['user', 'crawler']
            )->latest()->get());
    }
}
