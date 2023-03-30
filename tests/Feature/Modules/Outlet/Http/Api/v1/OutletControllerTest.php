<?php

namespace Tests\Feature\Modules\Outlet\Http\Api\v1;

use App\Modules\Outlet\Http\Controller\Api\v1\OutletController;
use App\Modules\Outlet\Model\Outlet;
use App\Modules\Outlet\Service\OutletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class OutletControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function testIndex(): void
    {
        $controller = new OutletController(new OutletService(new Outlet()));
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $outlet) {
            $this->assertInstanceOf(Outlet::class, $outlet);
        }
    }

    /** @test */
    public function testStore(): void
    {
        $request = new Request([
            'title' => 'title outlet',
            'outlet' => 'outlet',
            'domain' => 'cnbc.com',
            'url' => 'https://www.cnbc.com/',
            'pi' => -0.0221973135909118,
        ]);

        $controller = new OutletController(new OutletService(new Outlet()));
        $result = $controller->store($request);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
        $this->assertDatabaseHas('outlets', $request->all());
    }

    /** @test */
    public function testShow(): void
    {
        $outlet = Outlet::create([
            'user_id' => $this->user->id,
            'title' => 'title outlet',
            'outlet' => 'outlet',
            'domain' => 'cnbc.com',
            'url' => 'https://www.cnbc.com/',
            'pi' => -0.0221973135909118,
        ]);

        $controller = new OutletController(new OutletService(new Outlet()));
        $result = $controller->show($outlet->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
    }

    /** @test */
    public function testEdit(): void
    {
        $outletMock = Mockery::mock(Outlet::class);
        $outletMock->shouldReceive('create')
            ->once()
            ->andReturn(new Outlet([
                'id' => 2,
                'user_id' => $this->user->id,
                'title' => 'title outlet',
                'outlet' => 'outlet',
                'domain' => 'cnbc.com',
                'url' => 'https://www.cnbc.com/',
                'pi' => -0.0221973135909118,
            ]));

        $outlet = $outletMock->create();

        $controller = new OutletController(new OutletService(new Outlet()));
        $result = $controller->edit($outlet);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
        $this->assertEquals($outlet->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate(): void
    {
        $outlet = Outlet::create([
            'user_id' => $this->user->id,
            'title' => 'title outlet',
            'outlet' => 'outlet',
            'domain' => 'cnbc.com',
            'url' => 'https://www.cnbc.com/',
            'pi' => -0.0221973135909118,
        ]);

        $request = new Request([
            'title' => 'title outlet update',
            'outlet' => 'outlet update',
            'domain' => 'cnbc.com',
            'url' => 'https://www.cnbc.com/',
            'pi' => -0.0221973135909118,
        ]);

        $controller = new OutletController(new OutletService(new Outlet()));
        $result = $controller->update($request, $outlet);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
        $this->assertDatabaseHas('outlets', $request->all());
    }

    /** @test */
    public function testDestroy(): void
    {
        $outlet = Outlet::create([
            'user_id' => $this->user->id,
            'title' => 'title outlet',
            'outlet' => 'outlet',
            'domain' => 'cnbc.com',
            'url' => 'https://www.cnbc.com/',
            'pi' => -0.0221973135909118,
        ]);

        $controller = new OutletController(new OutletService(new Outlet()));
        $result = $controller->destroy($outlet);

        $this->assertDatabaseMissing('outlets', $outlet->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
    }
}
