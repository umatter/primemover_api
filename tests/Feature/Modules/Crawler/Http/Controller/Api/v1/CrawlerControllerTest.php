<?php

namespace Tests\Feature\Modules\Crawler\Http\Controller\Api\v1;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Http\Request\UpdateCrawlerRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Service\CrawlerService;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController
 */
class CrawlerControllerTest extends TestCase
{
    /**
     * @covers ::index
     */
    public function testIndex(): void
    {
        $request = new Request();

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->index($request);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
    }

    /**
     * @covers ::inactive
     */
    public function testInactive(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->inactive();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        if (!empty($result->resource[0])) {
            $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        }
    }

    /**
     * @covers ::withProcessedQueues
     */
    public function testWithProcessedQueues(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->withProcessedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::withReviewedQueues
     */
    public function testWithReviewedQueues(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->withReviewedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::withUnprocessedQueues
     */
    public function testWithUnprocessedQueues(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->withUnprocessedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::testing
     */
    public function testTesting(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->testing();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::create
     */
    public function testCreate(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->create();

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
    }

    /**
     * @covers ::store
     */
    public function testStore(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

        $data = [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'experiment_id' => $experiment->id,
            'active' => 0,
            'user_id' => $this->user->id,
        ];

        $requestMock = m::mock(StoreCrawlerRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
        $this->assertDatabaseHas('crawlers', $data);
    }

    /**
     * @covers ::show
     */
    public function testShow(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

        $crawlerMock = m::mock(Crawler::class);

        $crawlerMock->expects('firstOrFail')->andReturns(new Crawler([
            'id' => 1,
            'user_id' => 1,
            'name' => 'crawler name',
            'description' => 'crawler description',
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => $experiment->id,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->show($crawler);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
        $this->assertEquals($crawler->id, $result->resource->id);
    }

    /**
     * @covers ::edit
     */
    public function testEdit(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

        $crawlerMock = m::mock(Crawler::class);

        $crawlerMock->expects('firstOrFail')->andReturns(new Crawler([
            'id' => 1,
            'user_id' => 1,
            'name' => 'crawler name',
            'description' => 'crawler description',
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => $experiment->id,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->edit($crawler);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
        $this->assertEquals($crawler->id, $result->resource->id);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

        $crawler = m::mock(Crawler::class);

        $crawler->expects('firstOrFail')->andReturns(new Crawler([
            'user_id' => 1,
            'name' => 'crawler name',
            'description' => 'crawler description',
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => $experiment->id,
        ]));

        $data = [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'experiment_id' => $experiment->id,
        ];

        $requestMock = m::mock(UpdateCrawlerRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->update($requestMock, $crawler->firstOrFail());

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
    }

    /**
     * @covers ::disableProdCrawlers
     */
    public function testDisableProdCrawlers(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->disableProdCrawlers();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        $this->assertEquals(0, $result->resource[0]->active);
        $this->assertEquals(0, $result->resource[0]->testing);
    }

    /**
     * @covers ::forceDelete
     */
    public function testForceDelete(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->forceDelete($crawler);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        $this->assertDatabaseMissing('crawlers', ['id' => $crawler->id]);
    }

    /**
     * @covers ::destroy
     */
    public function testDestroy(): void
    {
        $experiment = Experiment::create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

        $crawlerMock = m::mock(Crawler::class);

        $crawlerMock->expects('firstOrFail')->andReturns(new Crawler([
            'id' => 1,
            'user_id' => 1,
            'name' => 'crawler name',
            'description' => 'crawler description',
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => $experiment->id,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->destroy($crawler);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
    }

    /**
     * @covers ::ip
     */
    public function testIp(): void
    {
        $request = new Request([
            'ip' => '127.0.0.2',
        ]);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->ip($request->ip);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /**
     * @covers ::undo
     */
    public function testUndo(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->undo();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        $this->assertDatabaseMissing('crawlers', ['updated' => 1]);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::cleanup
     */
    public function testCleanup(): void
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->cleanup();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        $this->assertDatabaseMissing('crawlers', ['testing' => 0]);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::load
     */
    public function testLoad(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $request = m::mock(Request::class)->makePartial();
        $request->expects('getContent')->andReturns(json_encode([
            [
                'user_id' => $this->user->id,
                'name' => 'crawler name',
                'description' => 'crawler Description',
                'active' => 1,
                'proxy_id' => $proxy->id,
                'agent_id' => $agent->id,
                'configuration_id' => $configuration->id,
                'experiment_id' => $experiment->id,
                'queues' => [
                    [
                        'name' => 'queue testing 1',
                        'description' => 'description testing 1',
                        'start_at' => '2023-03-16T15:40:13.996197Z',
                        'jobs' => [
                            [
                                'name' => 'job testing',
                                'type' => 'visitjob',
                                'description' => 'description testing job 1',
                                'behaviors' => [
                                    [
                                        'name' => 'behavior name',
                                        'description' => 'behavior description 1',
                                        'value' => 20,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));

        $this->app->instance(Request::class, $request);
        app()->bind('request', fn () => $request);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->load();

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /**
     * @covers ::makeQueues
     */
    public function testMakeQueues(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $data = [
            [
                'name' => 'queue testing 1',
                'description' => 'description testing 1',
                'start_at' => '2023-03-16T15:40:13.996197Z',
                'jobs' => [
                    [
                        'name' => 'job testing',
                        'type' => 'visitjob',
                        'description' => 'description testing job 1',
                        'behaviors' => [
                            [
                                'name' => 'behavior name',
                                'description' => 'behavior description 1',
                                'value' => 20,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'queue testing 2',
                'description' => 'description testing 2',
                'start_at' => '2023-03-16T15:40:13.996197Z',
                'jobs' => [
                    [
                        'name' => 'job testing',
                        'type' => 'waitjob',
                        'description' => 'description testing job 2',
                        'behaviors' => [
                            [
                                'name' => 'behavior name',
                                'description' => 'behavior description 2',
                                'value' => 'XPATH',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makeQueues($crawler, $data);

        $this->assertTrue(true);
    }

    /**
     * @covers ::makeJobs
     */
    public function testMakeJobs(): void
    {
        $queue = Queue::factory()->create();

        $data = [
            [
                'name' => 'job testing',
                'type' => 'visitjob',
                'description' => 'description testing job 1',
                'behaviors' => [
                    [
                        'name' => 'behavior name',
                        'description' => 'behavior description 1',
                        'value' => 20,
                    ],
                ],
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makeJobs($data, $queue);

        $this->assertTrue(true);
    }

    /**
     * @covers ::makeBehaviors
     */
    public function testMakeBehaviors(): void
    {
        $queue = Queue::factory()->create();
        $job = Job::factory()->create([
            'queue_id' => $queue->id,
        ]);

        $data = [
            [
                'name' => 'behavior name',
                'description' => 'behavior description 1',
                'value' => 20,
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makeBehaviors($data, $job);

        $this->assertTrue(true);
    }

    /**
     * @covers ::makeProxies
     */
    public function testMakeProxies(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $data = [
            [
                'name' => 'proxy name',
                'description' => 'proxy description',
                'hostname' => 'localhost',
                'port' => 8080,
                'username' => 'username',
                'password' => 'password',
                'type' => 'type',
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->makeProxies($data, $crawler);

        $this->assertInstanceOf(Proxy::class, $result);
        $this->assertDatabaseHas('proxies', $data[0]);
    }

    /**
     * @covers ::makeConfigurations
     */
    public function testMakeConfigurations(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $data = [
            [
                'name' => 'name',
                'description' => 'description',
                'active' => 1,
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->makeConfigurations($data, $crawler);

        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertDatabaseHas('configurations', $data[0]);
    }

    /**
     * @covers ::makePreferences
     */
    public function testMakePreferences(): void
    {
        $agent = Agent::factory()->create();

        $data = [
            [
                'name' => 'preferences name',
                'description' => 'description preference',
                'value' => 'value preference',
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makePreferences($data, $agent);

        $this->assertTrue(true);
    }

    /**
     * @covers ::makeAgents
     */
    public function testMakeAgents(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $data = [
            [
                'name' => 'age',
                'description' => 'description',
                'identification' => 'identification',
                'multilogin_id' => 'multilogin_id',
                'multilogin_profile' => 'multilogin_profile',
                'location' => 'location',
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->makeAgents($data, $crawler);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertDatabaseHas('agents', $result->toArray());
    }
}
