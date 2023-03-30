<?php

namespace Tests\Feature\Modules\Experiment\Http\Api\v1;

use App\Modules\Experiment\Http\Controller\Api\v1\ExperimentController;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Service\ExperimentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ExperimentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testIndex(): void
    {
        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
        }
    }

    /** @test */
    public function testAll(): void
    {
        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->all();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
            $this->assertDatabaseHas('experiments', $experiment->toArray());
        }
    }

    /** @test */
    public function testStore(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test-Experiment 1',
            'description' => 'Lorem ipsum dolor sit amet',
        ];

        $requestMock = Mockery::mock(ExperimentStoreRequest::class)->makePartial();
        $requestMock->shouldReceive('validated')->andReturn($data);

        $requestMock->shouldReceive('getPreferences')->andReturn(null);

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertDatabaseHas('experiments', ['id' => $result->resource->id]);
    }

    /** @test */
    public function testShow(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->show($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertEquals($experiment->id, $result->resource->id);
    }

    /** @test */
    public function testEdit(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->show($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertEquals($experiment->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate(): void
    {
        $experiment = Experiment::factory()->create();

        $data = [
            'name' => 'Test-Experiment 1',
            'description' => 'Lorem ipsum dolor sit amet',
            'contact' => 'Nobody',
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
    public function testDestory(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->destroy($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource['experiment']);
        $this->assertDatabaseMissing('experiments', ['id' => $result->resource['experiment']->id]);
        $this->assertEquals(true, $result->resource['deleted']);
    }

    /** @test */
    public function testForceDelete(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentService(new Experiment()));
        $result = $controller->forceDelete($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource['experiment']);
        $this->assertEquals(true, $result->resource['deleted']);
    }
}
