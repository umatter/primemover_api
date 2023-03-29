<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Http\Controller\Api\v1\QueueController;
use App\Modules\Queue\Http\Request\StoreQueueRequest;
use App\Modules\Queue\Http\Request\UpdateQueueRequest;
use App\Modules\Queue\Http\Service\QueueService;
use App\Modules\Queue\Model\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

class QueueControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive("input")->with("crawler")->andReturn(1);
        $request->shouldReceive("input")->with("sortBy")->andReturn("id");

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->index($request);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue)
            $this->assertInstanceOf(Queue::class, $queue);
    }

    /** @test */
    public function testStore()
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
            "name" => "Session",
            "description" => "Test",
            "crawler_id" => $crawler->id,
            "user_id" => $this->user->id
        ];

        $request = m::mock(StoreQueueRequest::class)->makePartial();
        $request->shouldReceive("validated")->andReturn($data);

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->store($request);

        $this->assertDatabaseHas("queues", $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /** @test */
    public function testShow()
    {
        $queue = Queue::factory()->create();

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->show($queue);

        $this->assertEquals($queue->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /** @test */
    public function testJob()
    {
        $job = Job::factory()->create();

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->job($job->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /** @test */
    public function testProcessed()
    {
        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->processed();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /** @test */
    public function testUnprocessed()
    {
        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->unprocessed();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
        }
    }

    /** @test */
    public function testReviewed()
    {
        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->reviewed();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
        }
    }

    /** @test */
    public function testUnreviewed()
    {
        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->unreviewed();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /** @test */
    public function testExperimentUnreviewed()
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            "experiment_id" => $experiment->id
        ]);

        Queue::factory()->create([
            "crawler_id" => $crawler->id,
            "reviewed" => 0,
            "processed" => 1,
        ]);

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->experimentUnreviewed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
            $this->assertEquals(1, $queue->processed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /** @test */
    public function testExperimentReviewed()
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            "experiment_id" => $experiment->id
        ]);

        Queue::factory()->create([
            "crawler_id" => $crawler->id,
            "reviewed" => 1,
        ]);

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->experimentReviewed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /** @test */
    public function testExperimentUnprocessed()
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            "experiment_id" => $experiment->id
        ]);

        Queue::factory()->create([
            "crawler_id" => $crawler->id,
            "processed" => 0,
        ]);

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->experimentUnprocessed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /** @test */
    public function testExperimentProcessed()
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            "experiment_id" => $experiment->id
        ]);

        Queue::factory()->create([
            "crawler_id" => $crawler->id,
            "processed" => 1,
        ]);

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->experimentProcessed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach ($result->resource as $queue){
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /** @test */
    public function testEdit()
    {
        $queue = Queue::factory()->create();
        
        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->edit($queue);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
        $this->assertEquals($queue->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate()
    {
        $queue = Queue::factory()->create();

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
            "name" => "Queue updated",
            "description" => "Test",
            "crawler_id" => $crawler->id,
        ];

        $request = m::mock(UpdateQueueRequest::class)->makePartial();
        $request->shouldReceive("validated")->andReturn($data);

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->update($request, $queue);

        $this->assertDatabaseHas("queues", $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /** @test */
    public function testDestroy()
    {
        $queue = Queue::factory()->create();

        $controller = new QueueController(new QueueService(new Queue()));
        $result = $controller->destroy($queue);

        $this->assertDatabaseMissing("queues", $queue->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);
    }
}
