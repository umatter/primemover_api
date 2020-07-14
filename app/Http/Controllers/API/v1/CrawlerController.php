<?php

namespace App\Http\Controllers\API\v1;

use App\Crawler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CrawlerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([

            'crawlers' => Crawler::latest()->with('proxy', 'agent', 'configuration', 'jobs')->get()

        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

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

        $crawler = Crawler::create($data);
        return $crawler;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Crawler  $crawler
     * @return \Illuminate\Http\Response
     */
    public function show(Crawler $crawler)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Crawler  $crawler
     * @return \Illuminate\Http\Response
     */
    public function edit(Crawler $crawler)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Crawler  $crawler
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Crawler $crawler)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Crawler  $crawler
     * @return \Illuminate\Http\Response
     */
    public function destroy(Crawler $crawler)
    {
        if($crawler) {
            $crawler->delete();
            return Crawler::latest()->get();
        }
        return false;
    }
}
