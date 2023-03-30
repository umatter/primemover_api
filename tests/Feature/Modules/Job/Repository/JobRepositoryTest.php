<?php

namespace Tests\Feature\Modules\Job\Repository;

use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use App\Modules\Queue\Model\Queue;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Repository\JobRepository
 */
class JobRepositoryTest extends TestCase
{
    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $queue = Queue::factory()->create();

        Job::factory()->create([
            'queue_id' => $queue->id,
        ]);

        $service = new JobRepository(new Job());
        $result = $service->getData($queue->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $job) {
            $this->assertInstanceOf(Job::class, $job);
        }
    }

    /**
     * @covers ::getById
     */
    public function testGetById(): void
    {
        $job = Job::factory()->create();

        $service = new JobRepository(new Job());
        $result = $service->getById($job->id);

        $this->assertInstanceOf(Job::class, $result);
        $this->assertEquals($job->id, $result->id);
    }

    /**
     * @covers ::save
     */
    public function testSave(): void
    {
        $queue = Queue::factory()->create();

        $data = [
            'user_id' => $this->user->id,
            'name' => 'job name testing',
            'queue_id' => $queue->id,
        ];

        $service = new JobRepository(new Job());
        $result = $service->save($data);

        $this->assertDatabaseHas('jobs', $data);
        $this->assertInstanceOf(Job::class, $result);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $data = [
            'name' => 'job name testing update',
        ];

        $job = Job::factory()->create();

        $service = new JobRepository(new Job());
        $result = $service->update($data, $job);

        $this->assertDatabaseHas('jobs', $data);
        $this->assertInstanceOf(Job::class, $result);
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void
    {
        $job = Job::factory()->create();

        $service = new JobRepository(new Job());
        $service->delete($job);

        $this->assertDatabaseMissing('jobs', $job->toArray());
        $this->assertTrue(true);
    }
}
