<?php

namespace App\Http\Controllers;

use App\Proxy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProxyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * @param  \App\Proxy  $proxy
     * @return \Illuminate\Http\Response
     */
    public function show(Proxy $proxy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Proxy  $proxy
     * @return \Illuminate\Http\Response
     */
    public function edit(Proxy $proxy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Proxy  $proxy
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Proxy $proxy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Proxy  $proxy
     * @return \Illuminate\Http\Response
     */
    public function destroy(Proxy $proxy)
    {
        if($proxy) {
            $proxy->delete();
            return Proxy::latest()->get();
        }
        return false;
    }
}
