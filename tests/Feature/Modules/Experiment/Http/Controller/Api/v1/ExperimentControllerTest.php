<?php

namespace Tests\Feature\Modules\Experiment\Http\Controller\Api\v1;

use App\Modules\Experiment\Http\Controller\Api\v1\ExperimentController;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Repository\ExperimentRepository;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Experiment\Http\Controller\Api\v1\ExperimentController
 */
class ExperimentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers ::index
     */
    public function testIndex(): void
    {
        $controller = new ExperimentController();
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
        }

        $this->actingAs($this->user)
            ->getJson('/api/v1/experiments')
            ->assertJsonStructure([
                "data" => [
                    [
                        "id",
                        "name"
                    ]
                ],
                "meta", "links"
            ])
            ->assertSuccessful();
    }

    /**
     * @covers ::all
     */
    public function testAll(): void
    {
        $controller = new ExperimentController(new ExperimentRepository(new Experiment()));
        $result = $controller->all();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
            $this->assertDatabaseHas('experiments', $experiment->toArray());
        }
    }

    /**
     * @covers ::store
     */
    public function testStore(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test-Experiment 1',
            'description' => 'Lorem ipsum dolor sit amet',
        ];

        $requestMock = Mockery::mock(ExperimentStoreRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $requestMock->allows('getPreferences')->andReturns(null);

        $controller = new ExperimentController(new ExperimentRepository(new Experiment()));
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertDatabaseHas('experiments', ['id' => $result->resource->id]);
    }

    /**
     * @covers ::show
     */
    public function testShow(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentRepository(new Experiment()));
        $result = $controller->show($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertEquals($experiment->id, $result->resource->id);
    }

    /**
     * @covers ::edit
     */
    public function testEdit(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentRepository(new Experiment()));
        $result = $controller->show($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
        $this->assertEquals($experiment->id, $result->resource->id);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $experiment = Experiment::factory()->create();

        $data = [
            'name' => 'Test-Experiment 1',
            'description' => 'Lorem ipsum dolor sit amet',
            'contact' => 'Nobody',
        ];

        $requestMock = Mockery::mock(ExperimentStoreRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $requestMock->allows('getPreferences')->andReturns(null);

        $controller = new ExperimentController(new ExperimentRepository(new Experiment()));
        $result = $controller->update($requestMock, $experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Experiment::class, $result->resource);
    }

    /**
     * @covers ::destroy
     *
     * @throws Exception
     */
    public function testDestroy(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController();
        $result = $controller->destroy($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertTrue($result->resource['status']);
    }

    /**
     * @covers ::forceDelete
     *
     * @throws Exception
     */
    public function testForceDelete(): void
    {
        $experiment = Experiment::factory()->create();

        $controller = new ExperimentController(new ExperimentRepository(new Experiment()));
        $result = $controller->forceDelete($experiment);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertTrue($result->resource['status']);
    }

    /**
     * @covers ::download
     */
    public function testDownload(): void
    {

        $this->actingAs($this->user)
            ->getJson('/api/v1/experiments/1/download')
            ->assertSuccessful()
            ->assertDownload();
    }
}
