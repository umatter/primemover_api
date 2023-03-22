<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Job\Http\Controller\Api\v1\JobController;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Http\Request\UpdateJobRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery;
use Tests\TestCase;

class JobControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        $request = Mockery::mock(Request::class);

        $request->shouldReceive("input")->with("queue")->once()->andReturn(1);

        $controller = new JobController();
        $result = $controller->index($request);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach($result->resource as $job)
            $this->assertInstanceOf(Job::class, $job);
        
    }

    /** @test */
    public function testStore()
    {
        $queue = Queue::factory()->create();

        $data = [
            "user_id" => $this->user->id,
            "name" => "job name",
            "queue_id" => $queue->id
        ];

        $requestMock = Mockery::mock(StoreJobRequest::class)->makePartial();
        $requestMock->shouldReceive('validated')->andReturn($data);

        $controller = new JobController();
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Job::class, $result->resource);
        $this->assertDatabaseHas("jobs", $data);
    }

    /** @test */
    public function testShow()
    {
        $job = Job::factory()->create();

        $controller = new JobController();
        $result = $controller->show($job->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertEquals($job->id, $result->resource->id);
    }

    /** @test */
    public function testEdit()
    {
        $job = Job::factory()->create();

        $controller = new JobController();
        $result = $controller->edit($job);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Job::class, $result->resource);
        $this->assertEquals($job, $result->resource);
    }

    /** @test */
    public function testUpdate()
    {
        $queue = Queue::factory()->create();

        $job = Job::factory()->create();

        $data = [
            "name" => "job name update",
            "queue_id" => $queue->id
        ];

        $requestMock = Mockery::mock(UpdateJobRequest::class)->makePartial();
        $requestMock->shouldReceive('validated')->andReturn($data);

        $controller = new JobController();
        $result = $controller->update($requestMock, $job);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Job::class, $result->resource);
        $this->assertDatabaseHas("jobs", $data);
    }

    /** @test */
    public function testDestroy()
    {
        $job = Job::factory()->create();

        $controller = new JobController();
        $result = $controller->destroy($job);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertDatabaseMissing("jobs", $job->toArray());
    }
}
