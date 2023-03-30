<?php

namespace Tests\Feature\Modules\Proxy\Http\Controller\Api\v1;

use App\Modules\Proxy\Http\Controller\Api\v1\ProxyController;
use App\Modules\Proxy\Http\Request\ProxyIndexRequest;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Proxy\Repository\ProxyRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Proxy\Http\Controller\Api\v1\ProxyController
 */
class ProxyControllerTest extends TestCase
{
    /**
     * @covers ::index
     */
    public function testIndex(): void
    {
        $proxyIndexRequest = Mockery::mock(ProxyIndexRequest::class)->makePartial();
        $proxyIndexRequest->allows('getPaged')->andReturns(1);

        $controller = new ProxyController(new ProxyRepository(new Proxy()));
        $result = $controller->index($proxyIndexRequest);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $proxy) {
            $this->assertInstanceOf(Proxy::class, $proxy);
        }
    }

    /**
     * @covers ::store
     */
    public function testStore(): void
    {
        $data = new Request([
            'name' => 'name',
            'description' => 'description',
            'hostname' => 'hostname',
            'port' => 8080,
            'username' => 'username',
            'password' => 'password',
            'type' => 'type',
            'active' => 0,
        ]);

        $controller = new ProxyController(new ProxyRepository(new Proxy()));
        $result = $controller->store($data);

        $this->assertDatabaseHas('proxies', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::show
     */
    public function testShow(): void
    {
        $proxy = Proxy::factory()->create();

        $controller = new ProxyController(new ProxyRepository(new Proxy()));
        $result = $controller->show($proxy);

        $this->assertEquals($proxy->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::edit
     */
    public function testEdit(): void
    {
        $proxy = Proxy::factory()->create();

        $controller = new ProxyController(new ProxyRepository(new Proxy()));
        $result = $controller->edit($proxy);

        $this->assertEquals($proxy->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $proxy = Proxy::factory()->create();

        $data = new Request([
            'name' => 'name updated',
            'description' => 'description',
            'hostname' => 'hostname',
            'port' => 8080,
            'username' => 'username',
            'password' => 'password',
            'type' => 'type',
            'active' => 0,
        ]);

        $controller = new ProxyController(new ProxyRepository(new Proxy()));
        $result = $controller->update($data, $proxy);

        $this->assertDatabaseHas('proxies', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::destroy
     */
    public function testDestroy(): void
    {
        $proxy = Proxy::factory()->create();

        $controller = new ProxyController(new ProxyRepository(new Proxy()));
        $result = $controller->destroy($proxy);

        $this->assertDatabaseMissing('proxies', $proxy->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $proxyItem) {
            $this->assertInstanceOf(Proxy::class, $proxyItem);
        }
    }
}
