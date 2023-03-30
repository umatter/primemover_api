<?php

namespace Tests\Feature\Modules\Proxy\Service;

use App\Modules\Proxy\Model\Proxy;
use App\Modules\Proxy\Service\ProxyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ProxyServiceTest extends TestCase
{
    /** @test */
    public function testGetData(): void
    {
        $data = m::mock(Request::class)->makePartial();
        $data->shouldReceive('getPaged')->andReturn(1);

        $service = new ProxyService(new Proxy());
        $result = $service->getData($data);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $proxy) {
            $this->assertInstanceOf(Proxy::class, $proxy);
            $this->assertDatabaseHas('proxies', $proxy->toArray());
        }
    }

    /** @test */
    public function testSave(): void
    {
        $data = [
            'name' => 'name',
            'description' => 'description',
            'hostname' => 'hostname',
            'port' => 8080,
            'username' => 'username',
            'password' => 'password',
            'type' => 'type',
            'active' => 0,
        ];

        $service = new ProxyService(new Proxy());
        $result = $service->save($data);

        $this->assertDatabaseHas('proxies', $data);
        $this->assertInstanceOf(Proxy::class, $result);
    }

    /** @test */
    public function testUpdate(): void
    {
        $proxy = Proxy::factory()->create();

        $data = [
            'name' => 'name updated',
            'description' => 'description updated',
        ];

        $service = new ProxyService(new Proxy());
        $result = $service->update($data, $proxy);

        $this->assertDatabaseHas('proxies', $data);
        $this->assertInstanceOf(Proxy::class, $result);
    }

    /** @test */
    public function testDelete(): void
    {
        $proxy = Proxy::factory()->create();

        $service = new ProxyService(new Proxy());
        $result = $service->delete($proxy);

        $this->assertDatabaseMissing('proxies', $proxy->toArray());
        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $proxy) {
            $this->assertInstanceOf(Proxy::class, $proxy);
        }
    }
}
