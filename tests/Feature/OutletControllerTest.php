<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Outlet\Http\Controller\Api\v1\OutletController;
use App\Modules\Outlet\Model\Outlet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class OutletControllerTest extends TestCase
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
        $controller = new OutletController();
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach($result->resource as $outlet)
            $this->assertInstanceOf(Outlet::class, $outlet);
    }

    /** @test */
    public function testStore()
    {
        $request = new Request([
            "title" => "title outlet",
            "outlet" => "outlet",
            "domain" => "cnbc.com",
            "url" => "https://www.cnbc.com/",
            "pi" => -0.0221973135909118
        ]);

        $controller = new OutletController();
        $result = $controller->store($request);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
        $this->assertDatabaseHas("outlets", $request->all());
    }

    /** @test */
    public function testShow()
    {
        $outlet = Outlet::create([
            "user_id" => $this->user->id,
            "title" => "title outlet",
            "outlet" => "outlet",
            "domain" => "cnbc.com",
            "url" => "https://www.cnbc.com/",
            "pi" => -0.0221973135909118
        ]);

        $controller = new OutletController();
        $result = $controller->show($outlet->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
    }

    /** @test */
    public function testEdit()
    {
        $outletMock = Mockery::mock(Outlet::class);
        $outletMock->shouldReceive("create")
                   ->once()
                   ->andReturn(new Outlet([
                        "id" => 2,
                        "user_id" => $this->user->id,
                        "title" => "title outlet",
                        "outlet" => "outlet",
                        "domain" => "cnbc.com",
                        "url" => "https://www.cnbc.com/",
                        "pi" => -0.0221973135909118
                    ]));

        $outlet = $outletMock->create();

        $controller = new OutletController();
        $result = $controller->edit($outlet);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
        $this->assertEquals($outlet->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate()
    {
        $outlet = Outlet::create([
            "user_id" => $this->user->id,
            "title" => "title outlet",
            "outlet" => "outlet",
            "domain" => "cnbc.com",
            "url" => "https://www.cnbc.com/",
            "pi" => -0.0221973135909118
        ]);

        $request = new Request([
            "title" => "title outlet update",
            "outlet" => "outlet update",
            "domain" => "cnbc.com",
            "url" => "https://www.cnbc.com/",
            "pi" => -0.0221973135909118
        ]);

        $controller = new OutletController();
        $result = $controller->update($request, $outlet);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Outlet::class, $result->resource);
        $this->assertDatabaseHas("outlets", $request->all());
    }

    /** @test */
    public function testDestroy()
    {
        $outlet = Outlet::create([
            "user_id" => $this->user->id,
            "title" => "title outlet",
            "outlet" => "outlet",
            "domain" => "cnbc.com",
            "url" => "https://www.cnbc.com/",
            "pi" => -0.0221973135909118
        ]);

        $controller = new OutletController();
        $result = $controller->destroy($outlet);

        $this->assertDatabaseMissing("outlets", $outlet->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
    }
}
