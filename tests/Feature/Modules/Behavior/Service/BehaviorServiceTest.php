<?php

namespace Tests\Feature\Modules\Behavior\Service;

use App\Modules\Behavior\Model\Behavior;
use App\Modules\Behavior\Service\BehaviorService;
use App\Modules\Job\Model\Job;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Behavior\Service\BehaviorService
 */
class BehaviorServiceTest extends TestCase
{
    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $service = new BehaviorService(new Behavior());
        $result = $service->getData();

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
    public function testGetById(): void
    {
        $behavior = Behavior::factory()->create();

        $service = new BehaviorService(new Behavior());
        $result = $service->getById($behavior->id);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertEquals($behavior->id, $result->id);
    }

    /**
     * @covers ::save
     */
    public function testSave(): void
    {
        $job = Job::factory()->create();

        $data = [
            'job_id' => $job->id,
            'name' => 'behavior name',
            'description' => 'behavior description',
            'value' => 'value',
            'active' => 1,
        ];

        $service = new BehaviorService(new Behavior());
        $result = $service->save($data);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertDatabaseHas('behaviors', $data);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $behavior = Behavior::factory()->create();

        $data = [
            'name' => 'behavior name updated',
            'description' => 'behavior description',
            'value' => 'value',
        ];

        $service = new BehaviorService(new Behavior());
        $result = $service->update($data, $behavior);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertDatabaseHas('behaviors', $data);
        $this->assertEquals($behavior->id, $result->id);
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void
    {
        $behavior = Behavior::factory()->create();

        $service = new BehaviorService(new Behavior());
        $service->delete($behavior);

        $this->assertTrue(true);
    }
}
