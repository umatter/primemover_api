<?php

namespace App\Http\Controllers\API\v1;

use App\Agent;
use App\Behavior;
use App\Configuration;
use App\Crawler;
use App\Job;
use App\Param;
use App\Proxy;
use App\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Arimolzer\IPStackFinder\Facade\IPStackFinderFacade;

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
        $crawlers = Crawler::where('testing', 0)
            ->where('active', 1)
            ->with('queues', 'agent', 'proxy', 'configuration')->latest()->get();
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
        $crawlers = Crawler::where('testing', 1)
            ->where('active', 1)
            ->with('queues', 'agent', 'proxy', 'configuration')->latest()->get();
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


    /**
     * Resolve IP
     * @param $ip
     * @return \Illuminate\Http\JsonResponse
     */
    public function ip($ip)
    {
        return response()->json([
            'result' => IPStackFinderFacade::get($ip)
        ], Response::HTTP_OK);
    }

    /**
     * Create new crawler from script, POST data sent
     */
    public function load()
    {
        $incoming = json_decode(request()->getContent(), true);
        return $this->makeCrawlers($incoming);
    }

    /**
     * Make a new crawler and other appended objects
     * @param $data
     */
    public function makeCrawlers($data)
    {
        foreach ($data as $crawler) {

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
            if ($crawler['agent']) {
                $agent = $this->makeAgents($crawler['agent']);
                $new->agent_id = $agent->id;
            }

            // Add proxy
            if ($crawler['proxy']) {
                $proxy = $this->makeProxies($crawler['proxy']);
                $new->proxy_id = $proxy->id;
            }

            // Add configuration
            if ($crawler['configuration']) {
                $configuration = $this->makeConfigurations($crawler['configuration']);
                $new->configuration_id = $configuration->id;
            }
            $new->update();
        }
    }

    /**
     * Creates Queues for Crawler
     * @param $data
     * @param $crawler
     */
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

    /**
     * Creates Jobs in Queues
     * @param $data
     * @param $queue
     */
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

    /**
     * Creates Behaviors for Jobs
     * @param $data
     * @param $job
     */
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

    /**
     * Creates Proxy for Crawler
     * @param $data
     * @return Proxy
     */
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

    /**
     * Creates Configuration with Params for Crawler
     * @param $data
     * @return Configuration
     */
    public function makeConfigurations($data)
    {
        $configuration = $data[0];
        $params = $configuration['params'];

        $new = new Configuration([
            "user_id" => 1,
            "name" => $configuration['name'],
            "description" => $configuration['description'],
        ]);
        $new->save();

        foreach ($params as $param) {
            $parameter = new Param([
                "user_id" => 1,
                "configuration_id" => $new->id,
                "pi" => $param['pi'],
                "psi" => $param['psi'],
                "kappa" => $param['kappa'],
                "tau" => $param['tau'],
                "beta" => $param['beta'],
                "alpha" => $param['alpha'],
                "search_terms" => json_encode($param['search_terms']),
                "media_outlet_urls" => json_encode($param['media_outlet_urls']),
            ]);
            $parameter->save();
        }

        return $new;
    }

    /**
     * Creates Agents for Crawler
     * @param $data
     * @return Agent
     */
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
            "location" => $agent['location']
        ]);
        $new->save();
        return $new;
    }

}
