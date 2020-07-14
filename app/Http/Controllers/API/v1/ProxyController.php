<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\v1\ProxyResource;
use App\Proxy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProxyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return response()->json([

            'proxies' => Proxy::latest()->get()

        ], Response::HTTP_OK);
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
        $data['user_id'] = auth()->user()->id;

        $proxy = Proxy::create($data);
        return $proxy;
    }

    /**
     * Display the specified resource.
     *
     * @param Proxy $proxy
     * @return Response
     */
    public function show($id): ProxyResource
    {
        $proxy = Proxy::with(['crawler'])->findOrFail($id);
        return new ProxyResource($proxy);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Proxy $proxy
     * @return Response
     */
    public function edit(Proxy $proxy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Proxy $proxy
     * @return Response
     */
    public function update(Request $request, Proxy $proxy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Proxy $proxy
     * @return Response
     */
    public function destroy(Proxy $proxy)
    {
        if ($proxy) {
            $proxy->delete();
            return Proxy::latest()->get();
        }
        return false;
    }
}
