<?php

namespace Tests\Feature\Modules\Job\Http\Controller\Api\v1;

use App\Modules\Job\Http\Controller\Api\v1\JobController;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Http\Request\UpdateJobRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Service\JobService;
use App\Modules\Queue\Model\Queue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Http\Controller\Api\v1\JobController
 */
class JobControllerTest extends TestCase
{
    /**
     * @covers ::index
     */
    public function testIndex(): void
    {
        $request = Mockery::mock(Request::class);

        $request->expects('input')->with('queue')->andReturns(1);

        $controller = new JobController(new JobService(new Job()));
        $result = $controller->index($request);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $job) {
            $this->assertInstanceOf(Job::class, $job);
        }
    }

    /**
     * @covers ::store
     */
    public function testStore(): void
    {
        $queue = Queue::factory()->create();

        $data = [
            'user_id' => $this->user->id,
            'name' => 'job name',
            'queue_id' => $queue->id,
        ];

        $requestMock = Mockery::mock(StoreJobRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $controller = new JobController(new JobService(new Job()));
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Job::class, $result->resource);
        $this->assertDatabaseHas('jobs', $data);
    }

    /**
     * @covers ::show
     */
    public function testShow(): void
    {
        $job = Job::factory()->create();

        $controller = new JobController(new JobService(new Job()));
        $result = $controller->show($job->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertEquals($job->id, $result->resource->id);
    }

    /**
     * @covers ::edit
     */
    public function testEdit(): void
    {
        $job = Job::factory()->create();

        $controller = new JobController(new JobService(new Job()));
        $result = $controller->edit($job);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Job::class, $result->resource);
        $this->assertEquals($job, $result->resource);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $queue = Queue::factory()->create();

        $job = Job::factory()->create();

        $data = [
            'name' => 'job name update',
            'queue_id' => $queue->id,
        ];

        $requestMock = Mockery::mock(UpdateJobRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $controller = new JobController(new JobService(new Job()));
        $result = $controller->update($requestMock, $job);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Job::class, $result->resource);
        $this->assertDatabaseHas('jobs', $data);
    }

    /**
     * @covers ::destroy
     */
    public function testDestroy(): void
    {
        $job = Job::factory()->create();

        $controller = new JobController(new JobService(new Job()));
        $result = $controller->destroy($job);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertDatabaseMissing('jobs', $job->toArray());
    }
}
