<?php

namespace Tests\Feature\Modules\Proxy\Repository;

use App\Modules\Proxy\Model\Proxy;
use App\Modules\Proxy\Repository\ProxyRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Proxy\Repository\ProxyRepository
 */
class ProxyRepositoryTest extends TestCase
{
    /**
     * @covers ::all
     */
    public function test_get_data(): void
    {
        $data = m::mock(Request::class)->makePartial();
        $data->allows('getPaged')->andReturns(1);

        $service = new ProxyRepository(new Proxy());
        $result = $service->all($data);

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $proxy) {
            $this->assertInstanceOf(Proxy::class, $proxy);
            $this->assertDatabaseHas('proxies', $proxy->toArray());
        }
    }

    /**
     * @covers ::save
     */
    public function test_save(): void
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

        $service = new ProxyRepository(new Proxy());
        $result = $service->save($data);

        $this->assertDatabaseHas('proxies', $data);
        $this->assertInstanceOf(Proxy::class, $result);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
    {
        $proxy = Proxy::factory()->create();

        $data = [
            'name' => 'name updated',
            'description' => 'description updated',
        ];

        $service = new ProxyRepository(new Proxy());
        $result = $service->update($data, $proxy);

        $this->assertDatabaseHas('proxies', $data);
        $this->assertInstanceOf(Proxy::class, $result);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void
    {
        $proxy = Proxy::factory()->create();

        $service = new ProxyRepository(new Proxy());
        $result = $service->delete($proxy);

        $this->assertDatabaseMissing('proxies', $proxy->toArray());
        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $proxy) {
            $this->assertInstanceOf(Proxy::class, $proxy);
        }
    }
}
