<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Behavior\Http\Service\BehaviorService;
use App\Modules\Behavior\Model\Behavior;
use App\Modules\Job\Model\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BehaviorServiceTest extends TestCase
{
    protected $user;

    public function setUp():void 
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testGetData()
    {
        $service = new BehaviorService(new Behavior());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        
        foreach($result as $behavior) {
            unset($behavior->job);
            unset($behavior->user);
            unset($behavior->updated_at);
            unset($behavior->created_at);
            $this->assertDatabaseHas("behaviors", $behavior->toArray());
            $this->assertInstanceOf(Behavior::class, $behavior);
        }
    }

    /** @test */
    public function testGetById()
    {
        $behavior = Behavior::factory()->create();

        $service = new BehaviorService(new Behavior());
        $result = $service->getById($behavior->id);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertEquals($behavior->id, $result->id);
    }

    /** @test */
    public function testSave()
    {
        $job = Job::factory()->create();

        $data = [
            'job_id' => $job->id,
            'name' => "behavior name",
            'description' => "behavior description",
            'value' => "value",
            'active' => 1,
        ];

        $service = new BehaviorService(new Behavior());
        $result = $service->save($data);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertDatabaseHas("behaviors", $data);
    }

    /** @test */
    public function testUpdate()
    {
        $behavior = Behavior::factory()->create();

        $data = [
            'name' => "behavior name updated",
            'description' => "behavior description",
            'value' => "value",
        ];

        $service = new BehaviorService(new Behavior());
        $result = $service->update($data, $behavior);

        $this->assertInstanceOf(Behavior::class, $result);
        $this->assertDatabaseHas("behaviors", $data);
        $this->assertEquals($behavior->id, $result->id);
    }

    /** @test */
    public function testDelete()
    {
        $behavior = Behavior::factory()->create();
        
        $service = new BehaviorService(new Behavior());
        $service->delete($behavior);

        $this->assertTrue(true);
    }
}
