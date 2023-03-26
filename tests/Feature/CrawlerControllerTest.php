<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Http\Request\UpdateCrawlerRequest;
use App\Modules\Crawler\Http\Service\CrawlerService;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;


class CrawlerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user, $crawler;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        $request = new Request();

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->index($request);
        
        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
    }

    /** @test */
    public function testInactive()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->inactive();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        if(!empty($result->resource[0])){
            $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        }
    }

    /** @test */
    public function testWithProcessedQueues()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->withProcessedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $crawler)
            $this->assertInstanceOf(Crawler::class, $crawler);
    }

    /** @test */
    public function testWithReviewedQueues()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->withReviewedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $crawler){
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /** @test */
    public function testWithUnprocessedQueues()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->withUnprocessedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $crawler){
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /** @test */
    public function testTesting()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->testing();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $crawler){
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /** @test */
    public function testCreate()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->create();

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
    }

    /** @test */
    public function testStore()
    {
        $experiment = Experiment::create([
            "user_id" => $this->user->id,
            "name" => "name experiment",
            "description" => "description experiment"
        ]);

        $data = [
            "name" => "Name",
            "description" => "Beschreibung",
            "experiment_id" => $experiment->id,
            "active" => 0,
            "user_id" => $this->user->id,
        ];

        $requestMock = m::mock(StoreCrawlerRequest::class)->makePartial();
        $requestMock->shouldReceive('validated')->andReturn($data);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
        $this->assertDatabaseHas("crawlers", $data);
    }

    /** @test */
    public function testShow()
    {
        $experiment = Experiment::create([
            "user_id" => $this->user->id,
            "name" => "name experiment",
            "description" => "description experiment"
        ]);

        $crawlerMock = m::mock(Crawler::class);

        $crawlerMock->shouldReceive("firstOrFail")->once()->andReturn(new Crawler([
            "id" => 1,
            'user_id' => 1,
            'name' => "crawler name",
            'description' => "crawler description",
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

    /** @test */
    public function testEdit()
    {
        $experiment = Experiment::create([
            "user_id" => $this->user->id,
            "name" => "name experiment",
            "description" => "description experiment"
        ]);

        $crawlerMock = m::mock(Crawler::class);

        $crawlerMock->shouldReceive("firstOrFail")->once()->andReturn(new Crawler([
            "id" => 1,
            'user_id' => 1,
            'name' => "crawler name",
            'description' => "crawler description",
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

    /** @test */
    public function testUpdate()
    {
        $experiment = Experiment::create([
            "user_id" => $this->user->id,
            "name" => "name experiment",
            "description" => "description experiment"
        ]);

        $crawler = m::mock(Crawler::class);

        $crawler->shouldReceive("firstOrFail")->once()->andReturn(new Crawler([
            'user_id' => 1,
            'name' => "crawler name",
            'description' => "crawler description",
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => $experiment->id,
        ]));

        $data = [
            "name" => "Name",
            "description" => "Beschreibung",
            "experiment_id" => $experiment->id
        ];

        $requestMock = m::mock(UpdateCrawlerRequest::class)->makePartial();
        $requestMock->shouldReceive('validated')->andReturn($data);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->update($requestMock, $crawler->firstOrFail());

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
    }

    /** @test */
    public function testDisableProdCrawlers()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->disableProdCrawlers();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        $this->assertEquals(0, $result->resource[0]->active);
        $this->assertEquals(0, $result->resource[0]->testing);
    }

    /** @test */
    public function testForceDelete()
    {
        
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            "proxy_id" => $proxy->id,
            "agent_id" => $agent->id,
            "configuration_id" => $configuration->id,
            "experiment_id" => $experiment->id
        ]);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->forceDelete($crawler);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        $this->assertDatabaseMissing("crawlers", ["id" => $crawler->id]);
    }
    
    /** @test */
    public function testDestroy()
    {
        $experiment = Experiment::create([
            "user_id" => $this->user->id,
            "name" => "name experiment",
            "description" => "description experiment"
        ]);

        $crawlerMock = m::mock(Crawler::class);

        $crawlerMock->shouldReceive("firstOrFail")->once()->andReturn(new Crawler([
            "id" => 1,
            'user_id' => 1,
            'name' => "crawler name",
            'description' => "crawler description",
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

    /** @test */
    public function testIp()
    {
        $request = new Request([
            "ip" => "127.0.0.2"
        ]);

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->ip($request->ip);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /** @test */
    public function testUndo()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->undo();


        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        
        $this->assertDatabaseMissing("crawlers", ["updated" => 1]);

        foreach($result->resource as $crawler){
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /** @test */
    public function testCleanup()
    {
        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->cleanup();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        
        $this->assertDatabaseMissing("crawlers", ["testing" => 0]);

        foreach($result->resource as $crawler){
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /** @test */
    public function testLoad()
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive("getContent")->once()->andReturn(json_encode([
            [
                'user_id' => $this->user->id,
                'name' => "crawler name",
                'description' => "crawler Description",
                'active' => 1,
                'proxy_id' => $proxy->id,
                'agent_id' => $agent->id,
                'configuration_id' => $configuration->id,
                'experiment_id' => $experiment->id,
                "queues" => [
                    [
                        "name" => "queue testing 1",
                        "description" => "description testing 1",
                        "start_at" =>"2023-03-16T15:40:13.996197Z",
                        "jobs" => [
                            [
                                "name" => "job testing",
                                "type" => "visitjob",
                                "description" => "description testing job 1",
                                "behaviors" => [
                                    [
                                        "name" => "behavior name",
                                        "description" => "behavior description 1",
                                        "value" => 20
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]));

        $this->app->instance(Request::class, $request);
        app()->bind('request', function() use ($request) {
            return $request;
        });

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->load();

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /** @test */
    public function testMakeQueues()
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            "proxy_id" => $proxy->id,
            "agent_id" => $agent->id,
            "configuration_id" => $configuration->id,
            "experiment_id" => $experiment->id
        ]);

        $data = [
            [
                "name" => "queue testing 1",
                "description" => "description testing 1",
                "start_at" =>"2023-03-16T15:40:13.996197Z",
                "jobs" => [
                    [
                        "name" => "job testing",
                        "type" => "visitjob",
                        "description" => "description testing job 1",
                        "behaviors" => [
                            [
                                "name" => "behavior name",
                                "description" => "behavior description 1",
                                "value" => 20
                            ]
                        ]
                    ]
                ]
            ],
            [
                "name" => "queue testing 2",
                "description" => "description testing 2",
                "start_at" =>"2023-03-16T15:40:13.996197Z",
                "jobs" => [
                    [
                        "name" => "job testing",
                        "type" => "waitjob",
                        "description" => "description testing job 2",
                        "behaviors" => [
                            [
                                "name" => "behavior name",
                                "description" => "behavior description 2",
                                "value" => "XPATH"
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makeQueues($crawler, $data);

        $this->assertTrue(true);
    }

    /** @test */
    public function testMakeJobs()
    {

        $queue = Queue::factory()->create();

        $data = [
            [
                "name" => "job testing",
                "type" => "visitjob",
                "description" => "description testing job 1",
                "behaviors" => [
                    [
                        "name" => "behavior name",
                        "description" => "behavior description 1",
                        "value" => 20
                    ]
                ]
            ]
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makeJobs($data, $queue);

        $this->assertTrue(true);
    }

    /** @test */
    public function testMakeBehaviors()
    {
        $queue = Queue::factory()->create();
        $job = Job::factory()->create([
            "queue_id" => $queue->id
        ]);

        $data = [
            [
                "name" => "behavior name",
                "description" => "behavior description 1",
                "value" => 20
            ]
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makeBehaviors($data, $job);
        
        $this->assertTrue(true);
    }
    
    /** @test */
    public function testMakeProxies()
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            "proxy_id" => $proxy->id,
            "agent_id" => $agent->id,
            "configuration_id" => $configuration->id,
            "experiment_id" => $experiment->id
        ]);

        $data = [
            [
                "name" => "proxy name",
                "description" => "proxy description",
                "hostname" => "localhost",
                "port" => 8080,
                "username" => "username",
                "password" => "password",
                "type" => "type",
            ]
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->makeProxies($data, $crawler);

        $this->assertInstanceOf(Proxy::class, $result);
        $this->assertDatabaseHas("proxies", $data[0]);
    }

    /** @test */
    public function testMakeConfigurations()
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            "proxy_id" => $proxy->id,
            "agent_id" => $agent->id,
            "configuration_id" => $configuration->id,
            "experiment_id" => $experiment->id
        ]);

        $data = [
            [
                "name" => "name",
                "description" => "description",
                "active" => 1,
            ]
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->makeConfigurations($data, $crawler);

        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertDatabaseHas("configurations", $data[0]);
    }

    /** @test */
    public function testMakePreferences()
    {
        $agent = Agent::factory()->create();

        $data = [
            [
                "name" => "preferences name",
                "description" => "description preference",
                "value" => "value preference"
            ]
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $controller->makePreferences($data, $agent);

        $this->assertTrue(true);
    }

    /** @test */
    public function testMakeAgents()
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            "proxy_id" => $proxy->id,
            "agent_id" => $agent->id,
            "configuration_id" => $configuration->id,
            "experiment_id" => $experiment->id
        ]);

        $data = [
            [
                "name" => "age",
                "description" => "description",
                "identification" => "identification",
                "multilogin_id" => "multilogin_id",
                "multilogin_profile" => "multilogin_profile",
                "location" => "location",
            ]
        ];

        $controller = new CrawlerController(new CrawlerService(new Crawler()));
        $result = $controller->makeAgents($data, $crawler);

        $this->assertInstanceOf(Agent::class, $result);
        $this->assertDatabaseHas("agents", $result->toArray());
    }
}
