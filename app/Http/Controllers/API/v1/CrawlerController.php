<?php

namespace App\Http\Controllers\API\v1;

use App\Crawler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Arimolzer\IPStackFinder\Exceptions\InvalidConfiguration;
use Arimolzer\IPStackFinder\Facade\IPStackFinderFacade;
use Arimolzer\IPStackFinder\IPStackFinder;

use App\Http\Resources\v1\CrawlerResource;
use App\Http\Resources\v1\CrawlerResourceCollection;

class CrawlerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request): CrawlerResourceCollection
    {
        /*
        return response()->json([

            'crawlers' => Crawler::latest()->with('proxy', 'agent', 'configuration', 'jobs')->get()

        ], Response::HTTP_OK);
        */

        $keyword = $request->input('keyword');
        $crawlers = Crawler::with('queues', 'agent', 'proxy', 'configuration')->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
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

        $crawler = Crawler::create($data);
        return $crawler;
    }

    /**
     * Display the specified resource.
     *
     * @param Crawler $crawler
     * @return Response
     */
    public function show($id): CrawlerResource
    {
        $crawler = Crawler::with('queues', 'agent', 'configuration', 'proxy')->findOrFail($id);
        return new CrawlerResource($crawler);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Crawler $crawler
     * @return Response
     */
    public function edit(Crawler $crawler)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Crawler $crawler
     * @return Response
     */
    public function update(Request $request, Crawler $crawler)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Crawler $crawler
     * @return Response
     */
    public function destroy(Crawler $crawler)
    {
        if ($crawler) {
            $crawler->delete();
            return Crawler::latest()->get();
        }
        return false;
    }

    public function ip($ip)
    {
        return response()->json([
            'result' => IPStackFinderFacade::get($ip)
        ], Response::HTTP_OK);
    }
}
