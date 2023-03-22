<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Configuration\Http\Controller\Api\v1\ConfigurationController;
use App\Modules\Configuration\Http\Request\ConfigurationStoreRequest;
use App\Modules\Configuration\Http\Request\ConfigurationUpdateRequest;
use App\Modules\Configuration\Model\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

class ConfigurationControllerTest extends TestCase
{
    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        $controller = new ConfigurationController();
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertArrayHasKey("id", $result->resource[0]);
        $this->assertInstanceOf(Configuration::class, $result->resource[0]);
    }

    /** @test */
    public function testStore()
    {
        $data = [
            "name" => "name",
            "description" => "description",
            "active" => 1,
            "user_id" => $this->user->id
        ];

        $request = m::mock(ConfigurationStoreRequest::class)->makePartial();
        $request->shouldReceive("validated")->andReturn($data);

        $controller = new ConfigurationController();
        $result = $controller->store($request);

        $this->assertDatabaseHas("configurations", $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Configuration::class, $result->resource);
    }

    /** @test */
    public function testShow()
    {
        $configuration = Configuration::factory()->create();
         
        $controller = new ConfigurationController();
        $result = $controller->show($configuration);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Configuration::class, $result->resource);
        $this->assertEquals($configuration->id, $result->resource->id);
    }

    /** @test */
    public function testEdit()
    {
        $configuration = Configuration::factory()->create();

        $controller = new ConfigurationController();
        $result = $controller->edit($configuration);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Configuration::class, $result->resource);
        $this->assertEquals($configuration->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate()
    {
        $configuration = Configuration::factory()->create();

        $data = [
            "name" => "testing updated",
            "description" => "description update",
            "active" => 1,
        ];

        $request = m::mock(ConfigurationUpdateRequest::class)->makePartial();
        $request->shouldReceive("validated")->andReturn($data);

        $controller = new ConfigurationController();
        $result = $controller->update($request, $configuration);

        $this->assertDatabaseHas("configurations", $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Configuration::class, $result->resource["data"]);
        $this->assertEquals(true, $result->resource["status"]);
    }
    
    /** @test */
    public function testDestroy()
    {
        $configuration = Configuration::factory()->create();

        $controller = new ConfigurationController();
        $result = $controller->destroy($configuration);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertEquals(true, $result->resource["status"]);
        $this->assertDatabaseMissing("configurations", ["id" => $configuration->id]);
    }
}
