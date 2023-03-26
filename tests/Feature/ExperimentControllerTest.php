<?php

namespace Tests\Feature;

use App\Facades\Services\DataMapper;
use App\Models\DTO\Preference;
use App\Models\User;
use App\Modules\Experiment\Http\Controller\Api\v1\ExperimentController;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Http\Service\ExperimentService;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Concerns\InteractsWithInput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class ExperimentControllerTest extends TestCase
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
        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        
        foreach($result->resource as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
        }
    }

    /** @test */
    public function testAll()
    {
        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->all();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach($result->resource as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
            $this->assertDatabaseHas("experiments", $experiment->toArray());
        }
    }

    /** @test */
    public function testStore()
    {
        $data = [
            "user_id" => $this->user->id,
            "name"=> "Test-Experiment 1",
            "description"=> "Lorem ipsum dolor sit amet"
        ];

        $requestMock = Mockery::mock(ExperimentStoreRequest::class)->makePartial();
        $requestMock->shouldReceive('validated')->andReturn($data);

        $requestMock->shouldReceive('getPreferences')->andReturn(null);

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertDatabaseHas("experiments", ["id" => $result->resource->id]);
    }

    /** @test */
    public function testShow()
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->show($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertEquals($experiment->id, $result->resource->id);
    }

    /** @test */
    public function testEdit()
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->show($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertEquals($experiment->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate()
    {
        $experiment = Experiment::factory()->create();

        $data = [
            "name"=> "Test-Experiment 1",
            "description"=> "Lorem ipsum dolor sit amet",
            "contact" => "Nobody",
        ];

        $requestMock = Mockery::mock(ExperimentStoreRequest::class)->makePartial();
        $requestMock->shouldReceive('validated')->andReturn($data);
        
        $requestMock->shouldReceive('getPreferences')->andReturn(null);

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->update($requestMock, $experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
    }

    /** @test */
    public function testDestory()
    {
        $experiment = Experiment::factory()->create();
        
        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->destroy($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource["experiment"]);
        $this->assertDatabaseMissing("experiments", ["id" => $result->resource["experiment"]->id]);
        $this->assertEquals(true, $result->resource["deleted"]);
    }

    /** @test */
    public function testForceDelete()
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->forceDelete($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource['experiment']);
        $this->assertEquals(true, $result->resource['deleted']);
    }
}
