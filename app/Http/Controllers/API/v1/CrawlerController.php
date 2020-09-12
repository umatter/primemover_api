<?php

namespace App\Http\Controllers\API\v1;

use App\Agent;
use App\Behavior;
use App\Configuration;
use App\Crawler;
use App\Job;
use App\Proxy;
use App\Queue;
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
        $crawlers = Crawler::where('testing', 0)->with('queues', 'agent', 'proxy', 'configuration')->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }

    /**
     * Display a listing of the resource for testing.
     *
     * @return Response
     */
    public function testing(Request $request): CrawlerResourceCollection
    {
        $keyword = $request->input('keyword');
        $crawlers = Crawler::where('testing', 1)->with('queues', 'agent', 'proxy', 'configuration')->latest()->get();
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

    public function load()
    {
        $incoming = json_decode(request()->getContent(), true);
        return $this->makeCrawlers($incoming);

    }

    public function makeCrawlers($data)
    {
        foreach ($data as $crawler) {

            // Premare data for a crawler
            $new = new Crawler([
                "user_id" => 1,
                "proxy_id" => 1,
                "configuration_id" => 1,
                "agent_id" => 1,
                "name" => $crawler['name'],
                "description" => $crawler['description'],
            ]);
            $new->save();

            // Create queues
            $this->makeQueues($crawler['queues'], $new);

            // Add agent
            if ($crawler['agents']) {
                $agent = $this->makeAgents($crawler['agents']);
                $new->agent_id = $agent->id;
            }

            // Add proxy
            if ($crawler['proxies']) {
                $proxy = $this->makeProxies($crawler['proxies']);
                $new->proxy_id = $proxy->id;
            }

            // Add configuration
            if ($crawler['configurations']) {
                $configuration = $this->makeConfigurations($crawler['configurations']);
                $new->configuration_id = $configuration->id;
            }
            $new->update();
        }
    }

    public function makeQueues($data, $crawler)
    {
        foreach ($data as $queue) {
            $new = new Queue([
                "user_id" => 1,
                "crawler_id" => $crawler->id,
                "name" => $queue['name'],
                "description" => $queue['description'],
                "start_at" => $queue['start_at'],
            ]);
            $new->save();
            $this->makeJobs($queue['jobs'], $new);
        }
    }

    public function makeJobs($data, $queue)
    {
        foreach ($data as $job) {
            $new = new Job([
                "user_id" => 1,
                "queue_id" => $queue->id,
                "name" => $job['name'],
                "type" => $job['type'],
                "description" => $job['description'],
            ]);
            $new->save();
            $this->makeBehaviors($job['behaviors'], $new);
        }
    }

    public function makeBehaviors($data, $job)
    {
        foreach ($data as $behavior) {
            $new = new Behavior([
                "user_id" => 1,
                "job_id" => $job->id,
                "name" => $behavior['name'],
                "description" => $behavior['description'],
                "value" => $behavior['value'],
            ]);
            $new->save();
        }
    }

    public function makeProxies($data)
    {
        $proxy = $data[0];
        $new = new Proxy([
            "user_id" => 1,
            "name" => $proxy['name'],
            "description" => $proxy['description'],
            "hostname" => $proxy['hostname'],
            "port" => $proxy['port'],
            "username" => $proxy['username'],
            "password" => $proxy['password'],
            "type" => $proxy['type'],
        ]);
        $new->save();
        return $new;
    }

    public function makeConfigurations($data)
    {
        $configuration = $data[0];
        $new = new Configuration([
            "user_id" => 1,
            "name" => $configuration['name'],
            "description" => $configuration['description'],
        ]);
        $new->save();
        return $new;
    }

    public function makeAgents($data)
    {
        $agent = $data[0];
        $new = new Agent([
            "user_id" => 1,
            "name" => $agent['name'],
            "description" => $agent['description'],
            "identification" => $agent['identification'],
            "multilogin_id" => $agent['multilogin_id'],
            "multilogin_profile" => $agent['multilogin_profile'],
        ]);
        $new->save();
        return $new;
    }

}
