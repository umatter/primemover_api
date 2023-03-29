<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Http\Service\JobService;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class JobServiceTest extends TestCase
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
        $queue = Queue::factory()->create();

        Job::factory()->create([
            "queue_id" => $queue->id
        ]);

        $service = new JobService(new Job());
        $result = $service->getData($queue->id);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $job)
            $this->assertInstanceOf(Job::class, $job);
    }

    /** @test */
    public function testGetById()
    {
        $job = Job::factory()->create();

        $service = new JobService(new Job());
        $result = $service->getById($job->id);

        $this->assertInstanceOf(Job::class, $result);
        $this->assertEquals($job->id, $result->id);
    }
    
    /** @test */
    public function testSave()
    {
        $queue = Queue::factory()->create();

        $data = [
            "user_id" => $this->user->id,
            "name" => "job name testing",
            "queue_id" => $queue->id
        ];

        $service = new JobService(new Job());
        $result = $service->save($data);

        $this->assertDatabaseHas("jobs", $data);
        $this->assertInstanceOf(Job::class, $result);
    }

    /** @test */
    public function testUpdate()
    {
        $data = [
            "name" => "job name testing update",
        ];

        $job = Job::factory()->create();

        $service = new JobService(new Job());
        $result = $service->update($data, $job);

        $this->assertDatabaseHas("jobs", $data);
        $this->assertInstanceOf(Job::class, $result);
    }

    /** @test */
    public function testDelete()
    {
        $job = Job::factory()->create();
        
        $service = new JobService(new Job());
        $service->delete($job);

        $this->assertDatabaseMissing("jobs", $job->toArray());
        $this->assertTrue(true);
    }
}
