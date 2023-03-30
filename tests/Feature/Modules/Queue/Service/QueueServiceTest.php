<?php

namespace Tests\Feature\Modules\Queue\Service;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Service\QueueService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Service\QueueService
 */
class QueueServiceTest extends TestCase
{
    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $sortBy = 'created_at';

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

        Queue::factory(5)->create(['crawler_id' => $crawler->id]);

        $service = new QueueService(new Queue());
        $result = $service->getData($crawler->id, $sortBy);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            unset($queue->crawler, $queue->created_at, $queue->updated_at);

            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertDatabaseHas('queues', $queue->toArray());
        }
    }

    /**
     * @covers ::save
     */
    public function testSave(): void
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
            'user_id' => $this->user->id,
            'name' => 'Session',
            'description' => 'Test',
            'crawler_id' => $crawler->id,
        ];

        $service = new QueueService(new Queue());
        $result = $service->save($data);

        $this->assertDatabaseHas('queues', $data);
        $this->assertInstanceOf(Queue::class, $result);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $data = [
            'name' => 'Session update',
            'description' => 'Test update',
        ];

        $queueMock = m::mock(Queue::class);
        $queueMock->allows('getAttribute')->with(['name', 'description'])->andReturns(['name' => 'Session', 'description' => 'Test']);
        $queueMock->allows('firstOrFail')->andReturns(new Queue([
            'user_id' => $this->user->id,
            'name' => 'Session',
            'description' => 'Test',
            'crawler_id' => '83',
        ]));

        $queue = $queueMock->firstOrFail();

        $queueMock->allows('update')->andReturnUsing(function () use ($queue, $data) {
            $queue->name = $data['name'];
            $queue->description = $data['description'];

            return true;
        });

        $service = new QueueService(new Queue());
        $result = $service->update($data, $queueMock);

        $this->assertInstanceOf(Queue::class, $result);
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

        $queue = Queue::factory()->create(['crawler_id' => $crawler->id]);

        $service = new QueueService(new Queue());
        $result = $service->delete($queue);

        $this->assertDatabaseMissing('queues', $queue->toArray());
        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
        }
    }

    /**
     * @covers ::job
     */
    public function testJob(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $job = Job::factory()->create();

        $service = new QueueService(new Queue());
        $result = $service->job($job->id);

        $this->assertInstanceOf(Queue::class, $result);
        $this->assertEquals($job->queue()->first(), $result);
    }

    /**
     * @covers ::processed
     */
    public function testProcessed(): void
    {
        $service = new QueueService(new Queue());
        $result = $service->processed();

        $this->assertInstanceOF(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /**
     * @covers ::unprocessed
     */
    public function testUnprocessed(): void
    {
        $service = new QueueService(new Queue());
        $result = $service->unprocessed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
        }
    }

    /**
     * @covers ::reviewed
     */
    public function testReviewed(): void
    {
        $service = new QueueService(new Queue());
        $result = $service->reviewed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
        }
    }

    /**
     * @covers ::unreviewed
     */
    public function testUnreviewed(): void
    {
        $service = new QueueService(new Queue());
        $result = $service->unreviewed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
        }
    }

    /**
     * @covers ::experimentUnreviewed
     */
    public function testExperimentUnreviewed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueService(new Queue());
        $result = $service->experimentUnreviewed($experiment);

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /**
     * @covers ::experimentReviewed
     */
    public function testExperimentReviewed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueService(new Queue());
        $result = $service->experimentReviewed($experiment);

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
        }
    }

    /**
     * @covers ::experimentUnprocessed
     */
    public function testExperimentUnprocessed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueService(new Queue());
        $result = $service->experimentUnprocessed($experiment);

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
        }
    }

    /**
     * @covers ::experimentProcessed
     */
    public function testExperimentProcessed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueService(new Queue());
        $result = $service->experimentProcessed($experiment);

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
        }
    }
}
