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
     * @covers ::all
     */
    public function test_get_data(): void
    {
        $queue = Queue::factory()->create();

        Job::factory()->create([
            'queue_id' => $queue->id,
        ]);

        $service = new JobRepository(new Job());
        $result = $service->all($queue->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $job) {
            $this->assertInstanceOf(Job::class, $job);
        }
    }

    /**
     * @covers ::getById
     */
    public function test_get_by_id(): void
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
    public function test_save(): void
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
    public function test_update(): void
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
    public function test_delete(): void
    {
        $job = Job::factory()->create();

        $service = new JobRepository(new Job());
        $service->delete($job);

        $this->assertDatabaseMissing('jobs', $job->toArray());
        $this->assertTrue(true);
    }
}
