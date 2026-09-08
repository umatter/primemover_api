<?php

namespace Tests\Feature\Modules\Queue\Repository;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Repository\QueueRepository
 */
class QueueRepositoryTest extends TestCase
{
    /**
     * @covers ::all
     */
    public function test_get_data(): void
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

        $service = new QueueRepository(new Queue());
        $result = $service->all($crawler->id, $sortBy);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            // The list query carries the job-outcome rollup (withJobOutcomeCounts),
            // and those are count subselects, not columns -- assertDatabaseHas
            // would look for a jobs_warning_count column and never find one.
            // Stripped via the constant rather than by name, so a rollup added
            // later does not fail here for the wrong reason.
            unset($queue->crawler, $queue->created_at, $queue->updated_at);

            foreach (Queue::JOB_OUTCOME_COUNT_ATTRIBUTES as $rollup) {
                unset($queue->{$rollup});
            }

            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertDatabaseHas('queues', $queue->toArray());
        }
    }

    /**
     * @covers ::save
     */
    public function test_save(): void
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

        $service = new QueueRepository(new Queue());
        $result = $service->save($data);

        $this->assertDatabaseHas('queues', $data);
        $this->assertInstanceOf(Queue::class, $result);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
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

        $queueMock->allows('fresh')->andReturnUsing(fn () => $queue);

        $service = new QueueRepository(new Queue());
        $result = $service->update($data, $queueMock);

        $this->assertInstanceOf(Queue::class, $result);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void
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

        $service = new QueueRepository(new Queue());
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
    public function test_job(): void
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

        $service = new QueueRepository(new Queue());
        $result = $service->job($job->id);

        $this->assertInstanceOf(Queue::class, $result);
        $this->assertEquals($job->queue()->first(), $result);
    }

    /**
     * @covers ::processed
     */
    public function test_processed(): void
    {
        $service = new QueueRepository(new Queue());
        $result = $service->processed();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /**
     * @covers ::unprocessed
     */
    public function test_unprocessed(): void
    {
        $service = new QueueRepository(new Queue());
        $result = $service->unprocessed();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
        }
    }

    /**
     * @covers ::reviewed
     */
    public function test_reviewed(): void
    {
        $service = new QueueRepository(new Queue());
        $result = $service->reviewed();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
        }
    }

    /**
     * @covers ::unreviewed
     */
    public function test_unreviewed(): void
    {
        $service = new QueueRepository(new Queue());
        $result = $service->unreviewed();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
        }
    }

    /**
     * @covers ::experimentUnreviewed
     */
    public function test_experiment_unreviewed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueRepository(new Queue());
        $result = $service->experimentUnreviewed($experiment);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->reviewed);
            $this->assertEquals(1, $queue->processed);
        }
    }

    /**
     * @covers ::experimentReviewed
     */
    public function test_experiment_reviewed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueRepository(new Queue());
        $result = $service->experimentReviewed($experiment);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->reviewed);
        }
    }

    /**
     * @covers ::experimentUnprocessed
     */
    public function test_experiment_unprocessed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueRepository(new Queue());
        $result = $service->experimentUnprocessed($experiment);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(0, $queue->processed);
        }
    }

    /**
     * @covers ::experimentProcessed
     */
    public function test_experiment_processed(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new QueueRepository(new Queue());
        $result = $service->experimentProcessed($experiment);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $queue) {
            $this->assertInstanceOf(Queue::class, $queue);
            $this->assertEquals(1, $queue->processed);
        }
    }
}
