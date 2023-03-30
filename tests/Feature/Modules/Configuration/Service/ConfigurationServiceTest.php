<?php

namespace Tests\Feature\Modules\Configuration\Service;

use App\Modules\Configuration\Model\Configuration;
use App\Modules\Configuration\Service\ConfigurationService;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\QueryBuilder;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ConfigurationServiceTest extends TestCase
{
    /** @test */
    public function testGetData(): void
    {
        $service = new ConfigurationService(new Configuration());
        $result = $service->getData();

        $this->assertInstanceOf(QueryBuilder::class, $result);
        $this->assertInstanceOf(Collection::class, $result->get());

        foreach ($result->get() as $configuration) {
            unset($configuration->params, $configuration->preferences);

            $this->assertDatabaseHas('configurations', $configuration->toArray());
            $this->assertInstanceOf(Configuration::class, $configuration);
        }
    }

    /** @test */
    public function testAdditional(): void
    {
        $service = new ConfigurationService(new Configuration());
        $configurations = $service->getData()->get();

        $result = $service->additional($configurations);

        $this->assertIsArray($result);
    }

    /** @test */
    public function testGetById(): void
    {
        $configuration = Configuration::factory()->create();

        $service = new ConfigurationService(new Configuration());
        $result = $service->getById($configuration);

        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertEquals($configuration->id, $result->id);
    }

    /** @test */
    public function testSave(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'name',
            'description' => 'description',
            'active' => 1,
        ];

        $service = new ConfigurationService(new Configuration());
        $result = $service->save($data);

        $this->assertDatabaseHas('configurations', $data);
        $this->assertInstanceOf(Configuration::class, $result);
    }

    /** @test */
    public function testUpdate(): void
    {
        $configuration = Configuration::factory()->create();

        $data = [
            'name' => 'name updated',
            'description' => 'description',
            'active' => 1,
        ];

        $service = new ConfigurationService(new Configuration());
        $result = $service->update($data, $configuration);

        $this->assertDatabaseHas('configurations', $data);
        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertInstanceOf(Configuration::class, $result['data']);
    }

    /** @test */
    public function testDelete(): void
    {
        $configuration = Configuration::factory()->create();

        $service = new ConfigurationService(new Configuration());
        $result = $service->delete($configuration);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
    }
}
