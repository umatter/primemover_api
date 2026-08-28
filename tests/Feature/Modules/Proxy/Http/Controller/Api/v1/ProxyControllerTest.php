<?php

namespace Tests\Feature\Modules\Proxy\Http\Controller\Api\v1;

use App\Modules\Proxy\Http\Controller\Api\v1\ProxyController;
use App\Modules\Proxy\Http\Request\ProxyIndexRequest;
use App\Modules\Proxy\Http\Request\ProxyStoreRequest;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Proxy\Http\Controller\Api\v1\ProxyController
 */
class ProxyControllerTest extends TestCase
{
    protected mixed $testObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testObject = App::make(ProxyController::class);
    }

    /**
     * @covers ::index
     */
    public function test_index(): void
    {
        $proxyIndexRequest = Mockery::mock(ProxyIndexRequest::class)->makePartial();
        $proxyIndexRequest->allows('getPaged')->andReturns(1);

        $result = $this->testObject->index($proxyIndexRequest);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $proxy) {
            $this->assertInstanceOf(Proxy::class, $proxy);
        }
    }

    /**
     * @covers ::store
     */
    public function test_store(): void
    {
        $request = Mockery::mock(ProxyStoreRequest::class);
        $request->expects('validated')
            ->twice()
            ->andReturns([
                'name' => 'name',
                'description' => 'description',
                'hostname' => 'hostname',
                'port' => 8080,
                'username' => 'username',
                'password' => 'password',
                'type' => 'type',
                'active' => 0,
            ]);

        $result = $this->testObject->store($request);

        $this->assertDatabaseHas('proxies', $request->validated());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::show
     */
    public function test_show(): void
    {
        $proxy = Proxy::factory()->create();

        $result = $this->testObject->show($proxy);

        $this->assertEquals($proxy->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::edit
     */
    public function test_edit(): void
    {
        $proxy = Proxy::factory()->create();

        $result = $this->testObject->edit($proxy);

        $this->assertEquals($proxy->id, $result->resource->id);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
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

        $result = $this->testObject->update($data, $proxy);

        $this->assertDatabaseHas('proxies', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Proxy::class, $result->resource);
    }

    /**
     * @covers ::destroy
     */
    public function test_destroy(): void
    {
        $proxy = Proxy::factory()->create();

        $result = $this->testObject->destroy($proxy);

        $this->assertDatabaseMissing('proxies', $proxy->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $proxyItem) {
            $this->assertInstanceOf(Proxy::class, $proxyItem);
        }
    }
}
