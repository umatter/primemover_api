<?php

namespace Tests\Feature\Modules\Crawler\Repository;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Repository\CrawlerRepository
 */
class CrawlerRepositoryTest extends TestCase
{
    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new CrawlerRepository(new Crawler());
        $result = $service->getData($experiment);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::getById
     */
    public function testGetById(): void
    {
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
            'experiment_id' => 1,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $service = new CrawlerRepository(new Crawler());
        $result = $service->getById($crawler);

        $this->assertInstanceOf(Crawler::class, $result);
        $this->assertEquals($crawler->id, $result->id);
    }

    /**
     * @covers ::inactive
     */
    public function testInactive(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->inactive();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::processed
     */
    public function testProcessed(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->processed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(1, $crawler->active);
            $this->assertEquals(0, $crawler->testing);
            foreach ($crawler->queues as $queue) {
                $this->assertEquals(1, $queue->processed);
            }
        }
    }

    /**
     * @covers ::unprocessed
     */
    public function testUnprocessed(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->unprocessed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(0, $crawler->testing);
            $this->assertEquals(1, $crawler->active);

            foreach ($crawler->queues as $queue) {
                $this->assertInstanceOf(Queue::class, $queue);
                $this->assertEquals(0, $queue->processed);
            }
        }
    }

    /**
     * @covers ::reviewed
     */
    public function testReviewed(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->reviewed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(1, $crawler->active);
            $this->assertEquals(1, $crawler->testing);

            foreach ($crawler->queues as $queue) {
                $this->assertInstanceOf(Queue::class, $queue);
                $this->assertEquals(1, $queue->reviewed);
            }
        }
    }

    /**
     * @covers ::testing
     */
    public function testTesting(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->testing();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(1, $crawler->testing);
            $this->assertEquals(1, $crawler->active);
        }
    }

    /**
     * @covers ::save
     */
    public function testSave(): void
    {
        $experiment = Experiment::factory()->create();

        $data = [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'experiment_id' => $experiment->id,
            'active' => 0,
            'user_id' => $this->user->id,
        ];

        $service = new CrawlerRepository(new Crawler());
        $result = $service->save($data);

        $this->assertDatabaseHas('crawlers', $data);
        $this->assertInstanceOf(Crawler::class, $result);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
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
            'experiment_id' => 1,
        ]));

        $data = [
            'name' => 'update name crawler',
            'active' => 1,
        ];

        $crawlerMock->allows('getAttribute')->with('id')->andReturns(1);
        $crawlerMock->allows('getAttribute')->with('name')->andReturns($data['name']);
        $crawlerMock->allows('getAttribute')->with('active')->andReturns($data['active']);

        $crawler = $crawlerMock->firstOrFail();

        $crawlerMock->expects('update')->andReturnUsing(function () use ($crawler, $data) {
            $crawler->name = $data['name'];
            $crawler->active = $data['active'];

            return true;
        });

        $service = new CrawlerRepository(new Crawler());
        $result = $service->update($data, $crawlerMock);

        $this->assertInstanceOf(Crawler::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['active'], $result->active);
    }

    /**
     * @covers ::disableProdCrawlers
     */
    public function testDisableProdCrawlers(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->disableProdCrawlers();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(0, $crawler->active);
        }
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

        $service = new CrawlerRepository(new Crawler());
        $result = $service->forceDelete($crawler);

        $crawler->setAppends([]);
        $this->assertDatabaseMissing('crawlers', $crawler->toArray());

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void
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

        $service = new CrawlerRepository(new Crawler());
        $result = $service->delete($crawler);

        $crawler->setAppends([]);
        $this->assertDatabaseMissing('crawlers', $crawler->toArray());
        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * @covers ::ip
     */
    public function testIp(): void
    {
        self::markTestIncomplete('This test has not been implemented yet.');
        $ip = '127.0.0.2';

        $service = new CrawlerRepository(new Crawler());
        $result = $service->ip($ip);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /**
     * @covers ::isLocalAddress
     */
    public function testIsLocalAddress(): void
    {
        $ip = '127.0.0.1';

        $service = new CrawlerRepository(new Crawler());
        $result = $service->isLocalAddress($ip);

        $this->assertIsBool($result);
    }

    /**
     * @covers ::undo
     */
    public function testUndo(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->undo();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertDatabaseMissing('crawlers', ['updated' => 1]);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::cleanup
     */
    public function testCleanUp(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->cleanup();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertDatabaseMissing('crawlers', ['testing' => 0]);

        foreach ($result as $crawler) {
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
                'description' => 'crawler description',
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

        $service = new CrawlerRepository(new Crawler());
        $result = $service->load();

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /**
     * @covers ::makeCrawlers
     */
    public function testMakeCrawlers(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $data = [
            [
                'user_id' => $this->user->id,
                'name' => 'crawler name',
                'description' => 'crawler description',
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
        ];

        $service = new CrawlerRepository(new Crawler());
        $result = $service->makeCrawlers($data);

        unset($data[0]['queues']);
        $this->assertDatabaseHas('crawlers', $data[0]);
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

        $service = new CrawlerRepository(new Crawler());
        $service->makeQueues($data, $crawler);

        unset($data[0]['jobs']);
        $this->assertDatabaseHas('queues', $data[0]);
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
                'name' => 'job name',
                'type' => 'visitjob',
                'description' => 'job description',
                'behaviors' => [
                    [
                        'name' => 'behavior name',
                        'description' => 'behavior description 1',
                        'value' => 20,
                    ],
                ],
            ],
        ];

        $service = new CrawlerRepository(new Crawler());
        $service->makeJobs($data, $queue);

        unset($data[0]['behaviors']);
        $this->assertDatabaseHas('jobs', $data[0]);
        $this->assertTrue(true);
    }

    /**
     * @covers ::makeBehaviors
     */
    public function testMakeBehaviors(): void
    {
        $job = Job::factory()->create();

        $data = [
            [
                'name' => 'behavior name',
                'description' => 'behavior description',
                'value' => 20,
            ],
        ];

        $service = new CrawlerRepository(new Crawler());
        $service->makeBehaviors($data, $job);

        $this->assertDatabaseHas('behaviors', $data[0]);
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

        $service = new CrawlerRepository(new Crawler());
        $result = $service->makeProxies($data, $crawler);

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
                'name' => 'configuration name',
                'description' => 'configuration description',
                'params' => [
                    [
                        'pi' => -0.50049837032537,
                        'psi' => 0.086705270313224,
                        'alpha' => 0.36951623165984,
                        'tau' => 1.0,
                        'kappa' => 1,
                        'beta' => 0.0034437549399552,
                        'search_terms' => [
                            [
                                'term' => 'black american',
                                'pi' => -0.424099419061494,
                            ],
                            [
                                'term' => 'proud boys stand',
                                'pi' => -0.483203551983422,
                            ],
                        ],
                        'media_outlet_urls' => [
                            [
                                'domain' => 'cnbc.com',
                                'url' => 'https://www.cnbc.com/',
                                'pi' => -0.0221973135909118,
                            ],
                        ],
                    ],
                ],
                'preferences' => [
                    [
                        'name' => 'location',
                        'value' => 'US-CO-COLORADO_SPRINGS',
                    ],
                    [
                        'name' => 'cookie_pref',
                        'value' => '{"accept_all": true}',
                    ],
                ],
            ],
        ];

        $service = new CrawlerRepository(new Crawler());
        $result = $service->makeConfigurations($data, $crawler);

        unset($data[0]['params'], $data[0]['preferences']);

        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertDatabaseHas('configurations', $data[0]);
    }

    /**
     * @covers ::makePreferences
     */
    public function testMakePreferences(): void
    {
        $configuration = Configuration::factory()->create();
        $data = [
            [
                'name' => 'preference name',
                'description' => 'preference description',
                'value' => 20,
            ],
        ];

        $service = new CrawlerRepository(new Crawler());
        $service->makePreferences($data, $configuration);

        $this->assertDatabaseHas('preferences', $data[0]);
        $this->assertTrue(true);
    }

    /**
     * @covers ::makeAgents
     */
    public function testMakeAgents(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create(['active' => 1]);
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
                'name' => 'agent name',
                'description' => 'agent description',
                'identification' => 'identification',
                'multilogin_id' => 'multilogin_id',
                'multilogin_profile' => 'multilogin_profile',
                'location' => 'location',
                'active' => 1,
            ],
        ];

        $service = new CrawlerRepository(new Crawler());
        $result = $service->makeAgents($data, $crawler);

        $data[0]['multilogin_profile'] = json_encode($data[0]['multilogin_profile']);
        $this->assertDatabaseHas('agents', $data[0]);
        $this->assertInstanceOf(Agent::class, $result);
    }
}
