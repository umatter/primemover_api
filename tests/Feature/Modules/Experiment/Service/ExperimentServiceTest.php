<?php

namespace Tests\Feature\Modules\Experiment\Service;

use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Service\ExperimentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ExperimentServiceTest extends TestCase
{
    /** @test */
    public function testGetData(): void
    {
        $service = new ExperimentService(new Experiment());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $experiment) {
            unset($experiment->created_at, $experiment->updated_at);

            $this->assertInstanceOf(Experiment::class, $experiment);
            $this->assertDatabaseHas('experiments', $experiment->toArray());
        }
    }

    /** @test */
    public function testAll(): void
    {
        $service = new ExperimentService(new Experiment());
        $result = $service->all();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
        }
    }

    /** @test */
    public function testGetById(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->getById($experiment);

        $this->assertInstanceOf(Experiment::class, $result);
        $this->assertEquals($experiment->id, $result->id);
    }

    /** @test */
    public function testSave(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test-Experiment 1',
            'description' => 'Lorem ipsum dolor sit amet',
        ];

        $mockData = m::mock($data);
        $mockData->shouldReceive('getPreferences')->andReturn(null);
        $mockData->shouldReceive('validated')->andReturn($data);

        $service = new ExperimentService(new Experiment());
        $result = $service->save($mockData);

        $this->assertInstanceOf(Experiment::class, $result);
        $this->assertDatabaseHas('experiments', $data);
    }

    /** @test */
    public function testUpdate(): void
    {
        $experiment = Experiment::factory()->create();

        $data = [
            'name' => 'Test-Experiment 1 updated',
            'description' => 'Lorem ipsum dolor sit amet',
        ];

        $mockData = m::mock($data)->makePartial();
        $mockData->shouldReceive('validated')->andReturn($data);
        $mockData->shouldReceive('getPreferences')->andReturn(false);

        $service = new ExperimentService(new Experiment());
        $result = $service->update($mockData, $experiment);

        $this->assertDatabaseHas('experiments', $data);
        $this->assertInstanceOf(Experiment::class, $result);
    }

    /** @test */
    public function testDelete(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->delete($experiment);

        $this->assertDatabaseMissing('experiments', $experiment->toArray());
        $this->assertTrue($result['deleted']);
        $this->assertInstanceOf(Experiment::class, $result['experiment']);
    }

    /** @test */
    public function forceDelete(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->forceDelete($experiment);

        $this->assertDatabaseMissing('experiments', $experiment->toArray());
        $this->assertTrue($result['deleted']);
        $this->assertInstanceOf(Experiment::class, $result['experiment']);
    }
}
