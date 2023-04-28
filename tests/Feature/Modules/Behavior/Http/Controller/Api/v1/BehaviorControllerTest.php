<?php

namespace Tests\Feature\Modules\Behavior\Http\Controller\Api\v1;

use App\Modules\Behavior\Http\Controller\Api\v1\BehaviorController;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Job\Model\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Behavior\Http\Controller\Api\v1\BehaviorController
 */
class BehaviorControllerTest extends TestCase
{
    protected mixed $testObject;

    public function setUp(): void
    {
        parent::setUp();
        $this->testObject = App::make(BehaviorController::class);
    }

    /**
     * @covers ::index
     */
    public function testIndex(): void
    {
        $result = $this->testObject->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $behavior) {
            $this->assertInstanceOf(Behavior::class, $behavior);
        }
    }

    /**
     * @covers ::store
     */
    public function testStore(): void
    {
        $job = Job::factory()->create();

        $data = new Request([
            'name' => 'behaviorName',
            'description' => 'description. Behavior',
            'value' => 'valueBehavior',
            'job_id' => $job->id,
        ]);

        $result = $this->testObject->store($data);

        $this->assertDatabaseHas('behaviors', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
    }

    /**
     * @covers ::edit
     */
    public function testEdit(): void
    {
        $behavior = Behavior::factory()->create();

        $result = $this->testObject->edit($behavior);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
        $this->assertEquals($behavior->id, $result->resource->id);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $behavior = Behavior::factory()->create();

        $request = new Request([
            'name' => 'name update',
            'description' => 'update description',
            'value' => 'update value',
            'active' => 0,
        ]);

        $result = $this->testObject->update($request, $behavior);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
        $this->assertEquals($behavior->id, $result->resource->id);
    }

    /**
     * @covers ::show
     */
    public function testShow(): void
    {
        $behavior = Behavior::factory()->create();

        $result = $this->testObject->show($behavior->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Behavior::class, $result->resource);
    }

    /**
     * @covers ::destroy
     */
    public function testDestroy(): void
    {
        $behavior = Behavior::factory()->create();

        $result = $this->testObject->destroy($behavior);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertDatabaseMissing('behaviors', ['id' => $behavior->id]);
    }
}
