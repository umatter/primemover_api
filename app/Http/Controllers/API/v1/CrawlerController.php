<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Agent;
use App\Models\Behavior;
use App\Models\Configuration;
use App\Models\Crawler;
use App\Models\Job;
use App\Models\Param;
use App\Models\Preference;
use App\Models\Proxy;
use App\Models\Queue;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class CrawlerController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $experimentId = $request->input('experiment');
        $crawlers = Crawler::query()->where('testing', 0)
            ->where('active', 1)
            ->with(['experiment'])
            ->when($experimentId, fn (Builder $query) => $query->where('experiment_id', $experimentId))
            ->latest()
            ->paginate(10);

        return ResourceCollection::make($crawlers);
    }

    public function inactive(): ResourceCollection
    {
        $crawlers = Crawler::query()
            ->where('testing', 0)
            ->where('active', 0)
            ->with(
                [
                    'queues',
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )->latest()->get();

        return ResourceCollection::make($crawlers);
    }

    public function withProcessedQueues(): ResourceCollection
    {
        $crawlers = Crawler::query()->where('testing', 0)
            ->where('active', 1)
            ->with(['queues' => function ($q) {
                $q->where('queues.processed', 1);
            }])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->whereHas('queues', function ($q) {
                $q->where('processed', 1);
            })
            ->latest()->get();

        return ResourceCollection::make($crawlers);
    }

    public function withReviewedQueues(): ResourceCollection
    {
        $crawlers = Crawler::query()->where('testing', 1)
            ->where('active', 1)
            ->with(['queues' => function ($q) {
                $q->where('queues.reviewed', 1);
            }])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->whereHas('queues', function ($q) {
                $q->where('reviewed', 1);
            })
            ->latest()->get();

        return ResourceCollection::make($crawlers);
    }

    public function withUnprocessedQueues(): ResourceCollection
    {
        $crawlers = Crawler::query()->where('testing', 0)
            ->where('active', 1)
            ->whereHas('queues', function ($q) {
                $q->where('processed', 0);
            })
            ->with(['queues' => function ($q) {
                $q->where('queues.processed', 0);
            }])
            ->with(
                [
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )
            ->latest()->get();

        return ResourceCollection::make($crawlers);
    }

    public function testing(): ResourceCollection
    {
        $crawlers = Crawler::query()->where('testing', 1)
            ->where('active', 1)
            ->with(['queues', 'agent.preferences', 'proxy', 'configuration.preferences'])->latest()
            ->paginate(10);

        return ResourceCollection::make($crawlers);
    }

    public function create(): JsonResource
    {
        return JsonResource::make(new Crawler());
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResource
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = Auth::id() ?? 1;
        $data['experiment_id'] = 1;

        $crawler = Crawler::query()->create($data);

        return JsonResource::make($crawler);
    }

    public function show($id): JsonResource
    {
        $crawler = Crawler::with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'configuration.preferences',
                'proxy',
                'user',
            ]
        )->findOrFail($id);

        return JsonResource::make($crawler);
    }

    public function edit(Crawler $crawler): JsonResource
    {
        return JsonResource::make($crawler);
    }

    public function update(Request $request, Crawler $crawler): JsonResource
    {
        $data = $request->all();
        $crawler->update($data);

        return JsonResource::make($crawler);
    }

    public function disableProdCrawlers(): ResourceCollection
    {
        Crawler::query()->where('testing', 0)->update(['active' => 0]);
        $crawler = Crawler::query()->where('active', 0)->get();

        return ResourceCollection::make($crawler);
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Crawler $crawler): ResourceCollection
    {
        $crawler->proxy()->delete();
        $crawler->agent()->delete();
        $crawler->configuration()->delete();
        $crawler->queues()->delete();
        $crawler->delete();

        return ResourceCollection::make(
            Crawler::with(
                [
                    'experiment',
                    'queues',
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )->latest()->get()
        );
    }

    public function destroy(Crawler $crawler): ResourceCollection
    {
        // $crawler->delete();
        return ResourceCollection::make(
            Crawler::with(
                [
                    'experiment',
                    'queues',
                    'agent.preferences',
                    'proxy',
                    'configuration.preferences',
                ]
            )->latest()->get()
        );
    }

    public function ip($ip = null): JsonResponse
    {
        $final = ($ip) ?: request()->ip();
        if (!$this->isLocalAddress($final)) {
            return response()->json([
                // 'result' => IPStackFinderFacade::get($final),
            ], ResponseAlias::HTTP_OK);
        }

        return $final;
    }

    public function isLocalAddress($ip): bool
    {
        return '::1' === $ip || '127.0.0.1' === $ip;
    }

    public function undo(): ResourceCollection
    {
        Crawler::query()->where('updated', 1)->delete();

        $crawlers = Crawler::with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
                'user',
            ]
        )->latest()->get();

        return ResourceCollection::make($crawlers);
    }

    public function cleanup(): ResourceCollection
    {
        Crawler::query()->where('testing', 0)->delete();

        $crawlers = Crawler::with(
            [
                'experiment',
                'queues',
                'agent.preferences',
                'proxy',
                'configuration.preferences',
                'user',
            ]
        )->latest()->get();

        return ResourceCollection::make($crawlers);
    }

    public function load(): JsonResponse
    {
        $incoming = json_decode(request()->getContent(), true);

        return $this->makeCrawlers($incoming);
    }

    public function makeCrawlers($data): JsonResponse
    {
        Crawler::query()->update(['new' => 0]);

        $crawlers = [];
        foreach ($data as $crawler) {
            $id = isset($crawler['id']) ? intval($crawler['id']) : null;

            /**
             * Create new or update if id provided.
             */
            $new = $id ? Crawler::query()->findOrFail($id) : new Crawler();

            $new->user_id = $crawler['user_id'] ?? 1;
            $new->experiment_id = 1;
            $new->name = $crawler['name'] ?? null;
            $new->description = $crawler['description'] ?? null;
            $new->testing = $crawler['testing'] ?? 0;
            $new->active = $crawler['active'] ?? 1;
            $new->updated = 1;

            if (isset($crawler['experiment_id'])) {
                $new->experiment_id = $crawler['experiment_id'];
            }

            if (isset($crawler['configuration_id'])) {
                $new->configuration_id = $crawler['configuration_id'];
            }

            if (isset($crawler['proxy_id'])) {
                $new->proxy_id = $crawler['proxy_id'];
            }

            if (isset($crawler['agent_id'])) {
                $new->agent_id = $crawler['agent_id'];
            }

            // Add agent
            if (isset($crawler['agent'])) {
                $resp = $this->makeAgents($crawler['agent'], $new);
                $new->agent_id = $resp->id;
            }

            // Add proxy
            if (isset($crawler['proxy'])) {
                $resp = $this->makeProxies($crawler['proxy'], $new);
                $new->proxy_id = $resp->id;
            }

            // Add configuration
            if (isset($crawler['configuration'])) {
                $resp = $this->makeConfigurations($crawler['configuration'], $new);
                $new->configuration_id = $resp->id;
            }

            // $new->update($payload);
            $new->save();

            // Create queues
            $this->makeQueues($crawler['queues'], $new);

            $new->update();
            array_push($crawlers, $new);
        }

        return response()->json([
            'crawlers' => Crawler::with(
                [
                    'proxy',
                    'agent.preferences',
                    'configuration.preferences',
                    'queuesNew',
                ]
            )
                ->where('updated', 1)
                ->get(),
        ], ResponseAlias::HTTP_OK);
    }

    public function makeQueues($data, $crawler)
    {
        Queue::query()->update(['new' => 0]);

        foreach ($data as $queue) {
            $new = new Queue([
                'user_id' => 1,
                'crawler_id' => $crawler->id,
                'name' => $queue['name'] ?? 'Value not provided at update!',
                'description' => $queue['description'] ?? 'Value not provided at update!',
                'start_at' => $queue['start_at'],
                'active' => 1,
            ]);
            $new->save();
            $this->makeJobs($queue['jobs'], $new);
        }
    }

    public function makeJobs($data, $queue)
    {
        foreach ($data as $job) {
            $new = new Job([
                'user_id' => 1,
                'queue_id' => $queue->id,
                'name' => $job['name'] ?? 'Value not provided at update',
                'type' => $job['type'] ?? 'Value not provided at update',
                'description' => $job['description'] ?? 'Value not provided at update',
            ]);
            $new->save();
            $this->makeBehaviors($job['behaviors'], $new);
        }
    }

    public function makeBehaviors($data, $job)
    {
        /*
         * selectionType=['XPATH', 'CSS', 'CLASS']
         * appendReturn=[true,false]
         * typeingMode=[]
         */
        foreach ($data as $behavior) {
            $new = new Behavior([
                'user_id' => 1,
                'job_id' => $job->id,
                'name' => $behavior['name'] ?? 'Value not provided at update!',
                'description' => $behavior['description'] ?? 'Value not provided at update!',
                'value' => $behavior['value'] ?? 'Value not provided at update!',
            ]);
            $new->save();
        }
    }

    public function checkBehaviorValues(string $name, string $value)
    {
        // todo
    }

    public function makeProxies($data, $crawler): Proxy
    {
        $proxy = $data[0];

        $payload = [
            'user_id' => 1,
            'name' => $proxy['name'] ?? null,
            'description' => $proxy['description'] ?? null,
            'hostname' => $proxy['hostname'] ?? null,
            'port' => $proxy['port'] ?? null,
            'username' => $proxy['username'] ?? null,
            'password' => $proxy['password'] ?? null,
            'type' => $proxy['type'] ?? null,
        ];

        $new = Proxy::query()->find($crawler->proxy_id);

        if ($crawler->proxy_id > 1 && $new) {
            $new->update($payload);
        } else {
            $new = new Proxy($payload);
            $new->save();
        }

        return $new;
    }

    public function log($content)
    {
        echo '<pre>';
        print_r($content);
        echo '</pre>';
    }

    public function makeConfigurations($data, $crawler): Configuration
    {
        $configuration = $data[0];
        $params = $configuration['params'] ?? null;
        $payload = [
            'user_id' => 1,
            'name' => $configuration['name'] ?? null,
            'active' => 1,
            'description' => $configuration['description'] ?? null,
        ];

        $new = Configuration::query()->find($crawler->configuration_id);

        if ($crawler->configuration_id > 1 && $new) {
            $new->update($payload);
        } else {
            $new = new Configuration($payload);
            $new->save();
        }

        if (isset($configuration['preferences']) && $configuration['preferences']) {
            $new->preferences()->delete();
            $this->makePreferences($configuration['preferences'], $new);
        }

        if (isset($params)) {
            $new->params()->delete();
            foreach ($params as $param) {
                $payload = [
                    'user_id' => 1,
                    'configuration_id' => $new->id,
                    'pi' => $param['pi'],
                    'psi' => $param['psi'],
                    'kappa' => $param['kappa'],
                    'tau' => $param['tau'],
                    'beta' => $param['beta'],
                    'alpha' => $param['alpha'],
                    'search_terms' => json_encode($param['search_terms']),
                    'media_outlet_urls' => json_encode($param['media_outlet_urls']),
                ];

                $parameter = new Param($payload);
                $parameter->save();
            }
        }

        return $new;
    }

    public function makePreferences($data, $model)
    {
        foreach ($data as $preference) {
            $new = new Preference([
                'user_id' => 1,
                'name' => $preference['name'] ?? 'Value not provided at update!',
                'description' => $preference['description'] ?? 'Value not provided at update!',
                'value' => $preference['value'] ?? 'Value not provided at update!',
            ]);
            $new->preferencable()->associate($model);
            $new->save();
        }
    }

    public function makeAgents($data, $crawler): Agent
    {
        $agent = $data[0];
        $payload = [
            'user_id' => 1,
            'name' => $agent['name'],
            'description' => $agent['description'],
            'identification' => $agent['identification'] ?: '',
            'multilogin_id' => $agent['multilogin_id'] ?: '',
            'multilogin_profile' => $agent['multilogin_profile'] ? json_encode($agent['multilogin_profile']) : '',
            'location' => $agent['location'] ?: '',
        ];

        $new = Agent::query()->find($crawler->agent_id);

        if ($crawler->agent_id > 1 && $new) {
            $new->update($payload);
        } else {
            $new = new Agent($payload);
            $new->save();
        }

        if (isset($agent['preferences']) && $agent['preferences']) {
            $this->makePreferences($agent['preferences'], $new);
        }

        return $new;
    }
}
