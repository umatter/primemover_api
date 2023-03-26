<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Experiment\Http\Service\ExperimentService;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use Mockery as m;

class ExperimentServiceTest extends TestCase
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
        $service = new ExperimentService(new Experiment());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->first());
    }

    /** @test */
    public function testAll()
    {
        $service = new ExperimentService(new Experiment());
        $result = $service->all();

        $this->assertInstanceOf(Collection::class, $result);
        
        foreach ($result as $experiment)
            $this->assertInstanceOf(Experiment::class, $experiment);
    }

    /** @test */
    public function testGetById()
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->getById($experiment);

        $this->assertInstanceOf(Experiment::class, $result);
        $this->assertEquals($experiment->id, $result->id);
    }

    /** @test */
    public function testSave()
    {
        $data = [
            "user_id" => $this->user->id,
            "name" => "Test-Experiment 1",
            "description" => "Lorem ipsum dolor sit amet",
        ];

        $mockData = m::mock($data);
        $mockData->shouldReceive("getPreferences")->andReturn(null);
        $mockData->shouldReceive("validated")->andReturn($data);

        $service = new ExperimentService(new Experiment());
        $result = $service->save($mockData);

        $this->assertInstanceOf(Experiment::class, $result);
        $this->assertDatabaseHas("experiments", $data);
    }

    /** @test */
    public function testUpdate()
    {
        $experiment = Experiment::factory()->create();

        $data = [
            "name" => "Test-Experiment 1 updated",
            "description" => "Lorem ipsum dolor sit amet",
        ];

        $mockData = m::mock($data)->makePartial();
        $mockData->shouldReceive("validated")->andReturn($data);
        $mockData->shouldReceive("getPreferences")->andReturn(false);

        $service = new ExperimentService(new Experiment());
        $result = $service->update($mockData, $experiment);

        $this->assertDatabaseHas("experiments", $data);
        $this->assertInstanceOf(Experiment::class, $result);
    }

    /** @test */
    public function testDelete()
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->delete($experiment);

        $this->assertDatabaseMissing("experiments", $experiment->toArray());
        $this->assertTrue($result["deleted"]);
        $this->assertInstanceOf(Experiment::class, $result["experiment"]);
    }

    /** @test */
    public function forceDelete()
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->forceDelete($experiment);

        $this->assertDatabaseMissing("experiments", $experiment->toArray());
        $this->assertTrue($result["deleted"]);
        $this->assertInstanceOf(Experiment::class, $result["experiment"]);
    }
}
