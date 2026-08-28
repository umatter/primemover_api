<?php

namespace Tests\Feature\Modules\Queue\Http\Controller\Api\v1;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Http\Controller\Api\v1\QueueController;
use App\Modules\Queue\Http\Request\StoreQueueRequest;
use App\Modules\Queue\Http\Request\UpdateQueueRequest;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Http\Controller\Api\v1\QueueController
 */
class QueueControllerTest extends TestCase
{
    /**
     * @covers ::index
     */
    public function test_index(): void
    {
        $request = m::mock(Request::class)->makePartial();
        $request->allows('input')->with('crawler')->andReturns(1);
        $request->allows('input')->with('sortBy')->andReturns('id');

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->index($request);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
        }
    }

    /**
     * @covers ::store
     */
    public function test_store(): void
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
            'name' => 'Session',
            'description' => 'Test',
            'crawler_id' => $crawler->id,
            'user_id' => $this->user->id,
        ];

        $request = m::mock(StoreQueueRequest::class)->makePartial();
        $request->allows('validated')->andReturns($data);

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->store($request);

        $this->assertDatabaseHas('queues', $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /**
     * @covers ::show
     */
    public function test_show(): void
    {
        $queue = Queue::factory()->create();

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->show($queue);

        $this->assertEquals($queue->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /**
     * @covers ::job
     */
    public function test_job(): void
    {
        $job = Job::factory()->create();

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->job($job->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /**
     * @covers ::processed
     */
    public function test_processed(): void
    {
        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->processed();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /**
     * @covers ::unprocessed
     */
    public function test_unprocessed(): void
    {
        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->unprocessed();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
        }
    }

    /**
     * @covers ::reviewed
     */
    public function test_reviewed(): void
    {
        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->reviewed();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
        }
    }

    /**
     * @covers ::unreviewed
     */
    public function test_unreviewed(): void
    {
        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->unreviewed();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /**
     * @covers ::experimentUnreviewed
     */
    public function test_experiment_unreviewed(): void
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            'experiment_id' => $experiment->id,
        ]);

        Queue::factory()->create([
            'crawler_id' => $crawler->id,
            'reviewed' => 0,
            'processed' => 1,
        ]);

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->experimentUnreviewed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
            $this->assertEquals(1, $queue->processed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /**
     * @covers ::experimentReviewed
     */
    public function test_experiment_reviewed(): void
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            'experiment_id' => $experiment->id,
        ]);

        Queue::factory()->create([
            'crawler_id' => $crawler->id,
            'reviewed' => 1,
        ]);

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->experimentReviewed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /**
     * @covers ::experimentUnprocessed
     */
    public function test_experiment_unprocessed(): void
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            'experiment_id' => $experiment->id,
        ]);

        Queue::factory()->create([
            'crawler_id' => $crawler->id,
            'processed' => 0,
        ]);

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->experimentUnprocessed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /**
     * @covers ::experimentProcessed
     */
    public function test_experiment_processed(): void
    {
        $experiment = Experiment::factory()->create();
        $crawler = Crawler::factory()->create([
            'experiment_id' => $experiment->id,
        ]);

        Queue::factory()->create([
            'crawler_id' => $crawler->id,
            'processed' => 1,
        ]);

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->experimentProcessed($experiment);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
            $this->assertEquals($experiment->id, $queue->crawler->experiment->id);
        }
    }

    /**
     * @covers ::edit
     */
    public function test_edit(): void
    {
        $queue = Queue::factory()->create();

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->edit($queue);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
        $this->assertEquals($queue->id, $result->resource->id);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
    {
        $queue = Queue::factory()->create();

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
            'name' => 'Queue updated',
            'description' => 'Test',
            'crawler_id' => $crawler->id,
        ];

        $request = m::mock(UpdateQueueRequest::class)->makePartial();
        $request->allows('validated')->andReturns($data);

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->update($request, $queue);

        $this->assertDatabaseHas('queues', $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Queue::class, $result->resource);
    }

    /**
     * @covers ::destroy
     */
    public function test_destroy(): void
    {
        $queue = Queue::factory()->create();

        $controller = new QueueController(new QueueRepository(new Queue()));
        $result = $controller->destroy($queue);

        $this->assertDatabaseMissing('queues', $queue->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);
    }
}
