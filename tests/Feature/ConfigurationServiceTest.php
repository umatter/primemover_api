<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Configuration\Http\Service\ConfigurationService;
use App\Modules\Configuration\Model\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\QueryBuilder\QueryBuilder;
use Tests\TestCase;

class ConfigurationServiceTest extends TestCase
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
        $service = new ConfigurationService(new Configuration());
        $result = $service->getData();

        $this->assertInstanceOf(QueryBuilder::class, $result);
        $this->assertInstanceOf(Configuration::class, $result->first());
    }

    /** @test */
    public function testAdditional()
    {
        $service = new ConfigurationService(new Configuration());
        $configurations = $service->getData()->get();

        $result = $service->additional($configurations);
        
        $this->assertIsArray($result);
    }

    /** @test */
    public function testGetById()
    {
        $configuration = Configuration::factory()->create();

        $service = new ConfigurationService(new Configuration());
        $result = $service->getById($configuration);

        $this->assertInstanceOf(Configuration::class, $result);
        $this->assertEquals($configuration->id, $result->id);
    }

    /** @test */
    public function testSave()
    {
        $data = [
            "user_id" => $this->user->id,
            "name" => "name",
            "description" => "description",
            "active" => 1,
        ];

        $service = new ConfigurationService(new Configuration());
        $result = $service->save($data);

        $this->assertDatabaseHas("configurations", $data);
        $this->assertInstanceOf(Configuration::class, $result);
    }

    /** @test */
    public function testUpdate()
    {
        $configuration = Configuration::factory()->create();

        $data = [
            "name" => "name updated",
            "description" => "description",
            "active" => 1,
        ];

        $service = new ConfigurationService(new Configuration());
        $result = $service->update($data, $configuration);

        $this->assertDatabaseHas("configurations", $data);
        $this->assertIsArray($result);
        $this->assertTrue($result["status"]);
        $this->assertInstanceOf(Configuration::class, $result["data"]);
    }

    /** @test */
    public function testDelete()
    {
        $configuration = Configuration::factory()->create();

        $service = new ConfigurationService(new Configuration());
        $result = $service->delete($configuration);

        $this->assertIsArray($result);
        $this->assertTrue($result["status"]);
    }
}
