<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Proxy\Http\Controller\Api\v1\ProxyController;
use App\Modules\Proxy\Http\Service\ProxyService;
use App\Modules\Proxy\Http\Request\ProxyIndexRequest;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery;
use Tests\TestCase;

class ProxyControllerTest extends TestCase
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
        $proxyIndexRequest = Mockery::mock(ProxyIndexRequest::class)->makePartial();
        $proxyIndexRequest->shouldReceive("getPaged")->andReturn(1);

        $controller = new ProxyController(new ProxyService(new Proxy()));
        $result = $controller->index($proxyIndexRequest);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach($result->resource as $proxy)
            $this->assertInstanceOf(Proxy::class, $proxy);
    }

    /** @test */
    public function testStore()
    {
        $data = new Request([
            "name" => "name",
            "description" => "description",
            "hostname" => "hostname",
            "port" => 8080,
            "username" => "username",
            "password" => "password",
            "type" => "type",
            "active" => 0
        ]);

        $controller = new ProxyController(new ProxyService(new Proxy()));
        $result = $controller->store($data);

        $this->assertDatabaseHas("proxies", $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /** @test */
    public function testShow()
    {
        $proxy = Proxy::factory()->create();

        $controller = new ProxyController(new ProxyService(new Proxy()));
        $result = $controller->show($proxy);

        $this->assertEquals($proxy->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /** @test */
    public function testEdit()
    {
        $proxy = Proxy::factory()->create();

        $controller = new ProxyController(new ProxyService(new Proxy()));
        $result = $controller->edit($proxy);

        $this->assertEquals($proxy->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /** @test */
    public function testUpdate()
    {
        $proxy = Proxy::factory()->create();

        $data = new Request([
            "name" => "name updated",
            "description" => "description",
            "hostname" => "hostname",
            "port" => 8080,
            "username" => "username",
            "password" => "password",
            "type" => "type",
            "active" => 0
        ]);

        $controller = new ProxyController(new ProxyService(new Proxy()));
        $result = $controller->update($data, $proxy);

        $this->assertDatabaseHas("proxies", $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /** @test */
    public function testDestroy()
    {
        $proxy = Proxy::factory()->create();

        $controller = new ProxyController(new ProxyService(new Proxy()));
        $result = $controller->destroy($proxy);

        $this->assertDatabaseMissing("proxies", $proxy->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $proxyItem)
            $this->assertInstanceOf(Proxy::class, $proxyItem);
    }
}
