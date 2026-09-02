<?php

namespace Tests\Feature\Modules\Behavior\Repository;

use App\Modules\Behavior\Model\Behavior;
use App\Modules\Behavior\Repository\BehaviorRepository;
use App\Modules\Job\Model\Job;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Behavior\Repository\BehaviorRepository
 */
class BehaviorRepositoryTest extends TestCase
{
    /**
     * @covers ::all
     */
    public function test_get_data(): void
    {
        $service = new BehaviorRepository(new Behavior());
        $result = $service->all();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $behavior) {
            unset($behavior->job, $behavior->user, $behavior->updated_at, $behavior->created_at);

            $this->assertDatabaseHas('behaviors', $behavior->toArray());
            $this->assertInstanceOf(Behavior::class, $behavior);
        }
    }

    /**
     * @covers ::getById
     */
    public function test_get_by_id(): void
    {
        $behavior = Behavior::factory()->create();

        $service = new BehaviorRepository(new Behavior());
        $result = $service->getById($behavior->id);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertEquals($behavior->id, $result->id);
    }

    /**
     * @covers ::save
     */
    public function test_save(): void
    {
        $job = Job::factory()->create();

        $data = [
            'job_id' => $job->id,
            'name' => 'behavior name',
            'description' => 'behavior description',
            'value' => 'value',
            'active' => 1,
        ];

        $service = new BehaviorRepository(new Behavior());
        $result = $service->save($data);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertDatabaseHas('behaviors', $data);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
    {
        $behavior = Behavior::factory()->create();

        $data = [
            'name' => 'behavior name updated',
            'description' => 'behavior description',
            'value' => 'value',
        ];

        $service = new BehaviorRepository(new Behavior());
        $result = $service->update($data, $behavior);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertDatabaseHas('behaviors', $data);
        $this->assertEquals($behavior->id, $result->id);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void
    {
        $behavior = Behavior::factory()->create();

        $service = new BehaviorRepository(new Behavior());
        $service->delete($behavior);

        $this->assertTrue(true);
    }
}
