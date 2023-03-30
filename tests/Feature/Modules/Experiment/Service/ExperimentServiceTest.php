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
 * @coversDefaultClass \App\Modules\Experiment\Service\ExperimentService
 */
class ExperimentServiceTest extends TestCase
{
    /**
     * @covers ::getData
     */
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

    /**
     * @covers ::all
     */
    public function testAll(): void
    {
        $service = new ExperimentService(new Experiment());
        $result = $service->all();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $experiment) {
            $this->assertInstanceOf(Experiment::class, $experiment);
        }
    }

    /**
     * @covers ::getById
     */
    public function testGetById(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->getById($experiment);

        $this->assertInstanceOf(Experiment::class, $result);
        $this->assertEquals($experiment->id, $result->id);
    }

    /**
     * @covers ::save
     */
    public function testSave(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test-Experiment 1',
            'description' => 'Lorem ipsum dolor sit amet',
        ];

        $mockData = m::mock($data);
        $mockData->allows('getPreferences')->andReturns(null);
        $mockData->allows('validated')->andReturns($data);

        $service = new ExperimentService(new Experiment());
        $result = $service->save($mockData);

        $this->assertInstanceOf(Experiment::class, $result);
        $this->assertDatabaseHas('experiments', $data);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $experiment = Experiment::factory()->create();

        $data = [
            'name' => 'Test-Experiment 1 updated',
            'description' => 'Lorem ipsum dolor sit amet',
        ];

        $mockData = m::mock($data)->makePartial();
        $mockData->allows('validated')->andReturns($data);
        $mockData->allows('getPreferences')->andReturns(false);

        $service = new ExperimentService(new Experiment());
        $result = $service->update($mockData, $experiment);

        $this->assertDatabaseHas('experiments', $data);
        $this->assertInstanceOf(Experiment::class, $result);
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new ExperimentService(new Experiment());
        $result = $service->delete($experiment);

        $this->assertDatabaseMissing('experiments', $experiment->toArray());
        $this->assertTrue($result['deleted']);
        $this->assertInstanceOf(Experiment::class, $result['experiment']);
    }

    /**
     * @covers ::forceDelete
     */
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
