<?php

namespace App\Http\Controllers\API\v1;

use App\Agent;
use App\Behavior;
use App\Configuration;
use App\Crawler;
use App\Job;
use App\Param;
use App\Preference;
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
        $keyword = $request->input('keyword');
        $crawlers = Crawler::where('testing', 0)
            ->where('active', 1)
            ->with(
                [
                    'queues',
                    'agent',
                    'proxy',
                    'configuration',
                ]
            )->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function inactive(Request $request): CrawlerResourceCollection
    {
        $keyword = $request->input('keyword');
        $crawlers = Crawler::where('testing', 0)
            ->where('active', 0)
            ->with(
                [
                    'queues',
                    'agent',
                    'proxy',
                    'configuration',
                ]
            )->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function withProcessedQueues(Request $request): CrawlerResourceCollection
    {
        $keyword = $request->input('keyword');
        $crawlers = Crawler::where('testing', 0)
            ->where('active', 1)
            ->with(['queues' => function ($q) {
                $q->where('queues.processed', 1);
            }])
            ->with(
                [
                    'agent',
                    'proxy',
                    'configuration',
                ]
            )
            ->whereHas('queues', function ($q) {
                $q->where('processed', 1);
            })
            ->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function withReviewedQueues(Request $request): CrawlerResourceCollection
    {
        $keyword = $request->input('keyword');
        $crawlers = Crawler::where('testing', 1)
            ->where('active', 1)
            ->with(['queues' => function ($q) {
                $q->where('queues.reviewed', 1);
            }])
            ->with(
                [
                    'agent',
                    'proxy',
                    'configuration',
                ]
            )
            ->whereHas('queues', function ($q) {
                $q->where('reviewed', 1);
            })
            ->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function withUnprocessedQueues(Request $request): CrawlerResourceCollection
    {
        $keyword = $request->input('keyword');
        $crawlers = Crawler::where('testing', 0)
            ->where('active', 1)
            ->whereHas('queues', function ($q) {
                $q->where('processed', 0);
            })
            ->with(['queues' => function ($q) {
                $q->where('queues.processed', 0);
            }])
            ->with(
                [
                    'agent',
                    'proxy',
                    'configuration',
                ]
            )
            ->latest()->get();
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
            ->with(
                [
                    'queues',
                    'agent',
                    'proxy',
                    'configuration',
                ]
            )->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(): CrawlerResource
    {
        return new CrawlerResource(new Crawler());
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return CrawlerResource
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): CrawlerResource
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        $data['experiment_id'] = 1;

        $crawler = Crawler::create($data);
        return new CrawlerResource($crawler);
    }

    /**
     * Display the specified resource.
     *
     * @param Crawler $crawler
     * @return Response
     */
    public function show($id): CrawlerResource
    {
        $crawler = Crawler::with(
            [
                'experiment',
                'queues',
                'agent',
                'configuration',
                'proxy',
                'user'
            ]
        )->findOrFail($id);
        return new CrawlerResource($crawler);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Crawler $crawler
     * @return Response
     */
    public function edit(Crawler $crawler): CrawlerResource
    {
        return new CrawlerResource($crawler);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Crawler $crawler
     * @return Response
     */
    public function update(Request $request, Crawler $crawler): CrawlerResource
    {
        $data = $request->all();
        $crawler->update($data);
        return new CrawlerResource($crawler);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Crawler $crawler
     * @return Response
     */
    public function disableProdCrawlers(): CrawlerResourceCollection
    {
        Crawler::query()->where('testing', 0)->update(['active' => 0]);
        $crawler = Crawler::where('active', 0)->get();
        return new CrawlerResourceCollection($crawler);
    }

    /**
     * Remove the specified resource from storage.
     * @param Crawler $crawler
     * @return CrawlerResourceCollection
     * @throws \Exception
     */

    public function destroy(Crawler $crawler): CrawlerResourceCollection
    {
        $crawler->delete();
        return new CrawlerResourceCollection(
            Crawler::with(
                [
                    'experiment',
                    'queues',
                    'agent',
                    'proxy',
                    'configuration'
                ]
            )->latest()->get()
        );
    }


    /**
     * Resolve IP
     * @param $ip
     * @return \Illuminate\Http\JsonResponse
     */
    public function ip($ip = null)
    {
        $final = ($ip) ? $ip : request()->ip();
        if (!$this->isLocalAddress($final)) {
            return response()->json([
                'result' => IPStackFinderFacade::get($final)
            ], Response::HTTP_OK);
        }
        return $final;
    }


    /**
     * Check if IP is local.
     * @param $ip
     * @return bool
     */
    public function isLocalAddress($ip)
    {
        return $ip === '::1' || $ip === '127.0.0.1';
    }

    /**
     * Remove records created in last load()
     * @return CrawlerResourceCollection
     */
    public function undo(): CrawlerResourceCollection
    {
        Crawler::where('updated', 1)->delete();

        $crawlers = Crawler::with(
            [
                'experiment',
                'queues',
                'agent',
                'proxy',
                'configuration',
                'user'
            ]
        )->latest()->get();
        return new CrawlerResourceCollection($crawlers);
    }

    /**
     * Remove all records matching a specific schema
     * @return CrawlerResourceCollection
     */
    public function cleanup(): CrawlerResourceCollection
    {
        Crawler::where('testing', 0)->delete();

        $crawlers = Crawler::with(
            [
                'experiment',
                'queues',
                'agent',
                'proxy',
                'configuration',
                'user'
            ]
        )->latest()->get();
        return new CrawlerResourceCollection($crawlers);
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
        Crawler::query()->update(['updated' => 0]);

        $crawlers = [];
        foreach ($data as $crawler) {
            $id = isset($crawler['id']) ? intval($crawler['id']) : null;
            $payload = [
                "user_id" => isset($crawler['user_id']) ? $crawler['user_id'] : 1,
                "experiment_id" => isset($crawler['experiment_id']) ? $crawler['experiment_id'] : 1,
                "proxy_id" => isset($crawler['proxy_id']) ? $crawler['proxy_id'] : 1,
                "configuration_id" => isset($crawler['configuration_id']) ? $crawler['configuration_id'] : 1,
                "agent_id" => isset($crawler['agent_id']) ? $crawler['agent_id'] : 1,
                "name" => isset($crawler['name']) ? $crawler['name'] : 'Value was not provided at update!',
                "description" => isset($crawler['description']) ? $crawler['description'] : 'Value was not provided at update!',
                "testing" => $crawler['testing'],
                "active" => $crawler['active'],
                "updated" => 1,
            ];

            /**
             * Create new or update if id provided.
             */
            if ($id) {
                $new = Crawler::findOrFail($id);
                $new->update($payload);
            } else {
                $new = new Crawler($payload);
                $new->save();
            }

            // Create queues
            $this->makeQueues($crawler['queues'], $new);

            // Add agent
            if (isset($crawler['agent'])) {
                $agent = $this->makeAgents($crawler['agent']);
                $new->agent_id = $agent->id;
            }

            // Add proxy
            if (isset($crawler['proxy'])) {
                $proxy = $this->makeProxies($crawler['proxy']);
                $new->proxy_id = $proxy->id;
            }

            // Add configuration
            if (isset($crawler['configuration'])) {
                $configuration = $this->makeConfigurations($crawler['configuration']);
                $new->configuration_id = $configuration->id;
            }
            $new->update();
            array_push($crawlers, $new);
        }

        return response()->json([
            'crawlers' => Crawler::with(
                [
                    'proxy',
                    'agent',
                    'configuration',
                    'queues',
                    'user'
                ]
            )
                ->where('updated', 1)
                ->get()
        ], Response::HTTP_OK);
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
                "name" => isset($queue['name']) ? $queue['name'] : 'Value not provided at update!',
                "description" => isset($queue['description']) ? $queue['description'] : 'Value not provided at update!',
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
                "name" => isset($job['name']) ? $job['name'] : 'Value not provided at update',
                "type" => isset($job['type']) ? $job['type'] : 'Value not provided at update',
                "description" => isset($job['description']) ? $job['description'] : 'Value not provided at update',
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
                "name" => isset($behavior['name']) ? $behavior['name'] : 'Value not provided at update!',
                "description" => isset($behavior['description']) ? $behavior['description'] : 'Value not provided at update!',
                "value" => isset($behavior['value']) ? $behavior['value'] : 'Value not provided at update!',
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
            "name" => isset($proxy['name']) ? $proxy['name'] : 'Value not provided at update',
            "description" => isset($proxy['description']) ? $proxy['description'] : 'Value not provided at update',
            "hostname" => isset($proxy['hostname']) ? $proxy['hostname'] : 'Value not provided at update',
            "port" => isset($proxy['port']) ? $proxy['port'] : 'Value not provided at update',
            "username" => isset($proxy['username']) ? $proxy['username'] : 'Value not provided at update',
            "password" => isset($proxy['password']) ? $proxy['password'] : 'Value not provided at update',
            "type" => isset($proxy['type']) ? $proxy['type'] : 'Value not provided at update',
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
        $params = isset($configuration['params']) ? $configuration['params'] : null;

        $new = new Configuration([
            "user_id" => 1,
            "name" => $configuration['name'],
            "description" => $configuration['description'],
        ]);

        $new->save();
        if (isset($configuration['$preferences']) && $configuration['$preferences']) {
            $this->makePreferences($configuration['preferences'], $new);
        }

        if (isset($params)) {
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
        }

        return $new;
    }

    /**
     * Creates Preferences for Configurations
     * @param $data
     * @param $model
     */
    public function makePreferences($data, $model)
    {
        foreach ($data as $preference) {
            $new = new Preference([
                "user_id" => 1,
                "name" => isset($preference['name']) ? $preference['name'] : 'Value not provided at update!',
                "description" => isset($preference['description']) ? $preference['description'] : 'Value not provided at update!',
                "value" => isset($preference['value']) ? $preference['value'] : 'Value not provided at update!',
            ]);
            $new->preferencable()->associate($model);
            $new->save();
        }
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
            "identification" => $agent['identification'] ? $agent['identification'] : "",
            "multilogin_id" => $agent['multilogin_id'] ? $agent['multilogin_id'] : "",
            "multilogin_profile" => $agent['multilogin_profile'] ? json_encode($agent['multilogin_profile']) : "",
            "location" => $agent['location'] ? $agent['location'] : "",
        ]);
        $new->save();
        if (isset($agent['$preferences']) && $agent['$preferences']) {
            $this->makePreferences($agent['preferences'], $new);
        }
        return $new;
    }

}
