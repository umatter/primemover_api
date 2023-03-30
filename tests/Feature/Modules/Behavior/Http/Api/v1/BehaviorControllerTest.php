<?php

namespace Tests\Feature\Modules\Behavior\Http\Api\v1;

use App\Modules\Behavior\Http\Controller\Api\v1\BehaviorController;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Behavior\Service\BehaviorService;
use App\Modules\Job\Model\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class BehaviorControllerTest extends TestCase
{
    /** @test */
    public function testIndex(): void
    {
        $controller = new BehaviorController(new BehaviorService(new Behavior()));
        $result = $controller->index();

        $this->actingAs($this->user)
            ->getJson('/api/v1/behaviors')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $behavior) {
            $this->assertInstanceOf(Behavior::class, $behavior);
        }
    }

    /** @test */
    public function testStore(): void
    {
        $job = Job::factory()->create();

        $data = new Request([
            'name' => 'behaviorName',
            'description' => 'description. Behavior',
            'value' => 'valueBehavior',
            'job_id' => $job->id,
        ]);

        $controller = new BehaviorController(new BehaviorService(new Behavior()));
        $result = $controller->store($data);

        $this->assertDatabaseHas('behaviors', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
    }

    /** @test */
    public function testEdit(): void
    {
        $behavior = Behavior::factory()->create();

        $controller = new BehaviorController(new BehaviorService(new Behavior()));
        $result = $controller->edit($behavior);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
        $this->assertEquals($behavior->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate(): void
    {
        $behavior = Behavior::factory()->create();

        $request = new Request([
            'name' => 'name update',
            'description' => 'update description',
            'value' => 'update value',
            'active' => 0,
        ]);

        $controller = new BehaviorController(new BehaviorService(new Behavior()));
        $result = $controller->update($request, $behavior);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
        $this->assertEquals($behavior->id, $result->resource->id);
    }

    /** @test */
    public function testShow(): void
    {
        $behavior = Behavior::factory()->create();

        $controller = new BehaviorController(new BehaviorService(new Behavior()));
        $result = $controller->show($behavior->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
    }

    public function testDestroy(): void
    {
        $behavior = Behavior::factory()->create();

        $controller = new BehaviorController(new BehaviorService(new Behavior()));
        $result = $controller->destroy($behavior);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertDatabaseMissing('behaviors', ['id' => $behavior->id]);
    }
}
