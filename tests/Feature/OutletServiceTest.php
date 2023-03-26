<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Outlet\Http\Service\OutletService;
use App\Modules\Outlet\Model\Outlet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery as m;

class OutletServiceTest extends TestCase
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
        $service = new OutletService(new Outlet());
        $result = $service->getData();

        $this->assertInstanceOf(Collection::class, $result);

        foreach($result as $outlet){
            unset($outlet->user);
            unset($outlet->updated_at);
            unset($outlet->created_at);
            $this->assertInstanceOf(Outlet::class, $outlet);
            $this->assertDatabaseHas("outlets", $outlet->toArray());
        }
    }

    /** @test */
    public function testGetById()
    {
        $outlet = m::mock(Outlet::class);
        $outlet->shouldReceive("getAttribute")->with("id")->andReturn(1);
        $outlet->shouldReceive("with")->with(["user"])->andReturn(new User([
            "id" => 1,
            'name' => "user name",
            'email' => "user@example.com",
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ]));
        $outlet->shouldReceive("findOrFail")->andReturn(new Outlet([
            "id" => 1,
            "title" => "outlet title",
            "outlet" => "outlet",
        ]));

        $service = new OutletService(new Outlet());
        $result = $service->getById($outlet->id);

        $this->assertInstanceOf(Outlet::class, $result);
        $this->assertInstanceOf(User::class, $result->user);
        $this->assertEquals($outlet->id, $result->id);
    }

    /** @test */
    public function testSave()
    {
        $data = [
            "title" => "outlet title",
            "outlet" => "outlet",
        ];

        $service = new OutletService(new Outlet());
        $result = $service->save($data);

        $this->assertDatabaseHas("outlets", $data);
        $this->assertInstanceOf(Outlet::class, $result);
    }

    /** @test */
    public function testUpdate()
    {
        $data = [
            "title" => "outlet title updated",
            "outlet" => "outlet update",
        ];

        $outletMock = m::mock(Outlet::class);
        $outletMock->shouldReceive("firstOrFail")->andReturn(new Outlet([
            "id" => 1,
            "title" => "outlet title",
            "outlet" => "outlet",
        ]));
        $outletMock->shouldReceive("getAttribute")->with(["title", "outlet"])->andReturn(["title" => "outlet title", "outlet" => "outlet"]);
        $outletMock->shouldReceive("toArray")->andReturn($data);
        $outlet = $outletMock->firstOrFail();
        
        $outletMock->shouldReceive("update")->andReturnUsing(function () use ($outlet, $data) {
            $outlet->title = $data["title"];
            $outlet->outlet = $data["outlet"];
            return true;
        });

        $service = new OutletService(new Outlet());
        $result = $service->update($data, $outletMock);

        $this->assertInstanceOf(Outlet::class, $result);
        $this->assertEquals($data, $outletMock->toArray());
    }

    /** @test */
    public function testDelete()
    {
        $outlet = Outlet::create([
            "user_id" => $this->user->id,
            "title" => "outlet title",
            "outlet" => "outlet"
        ]);

        $service = new OutletService(new Outlet());
        $result = $service->delete($outlet);

        $this->assertDatabaseMissing("outlets", $outlet->toArray());
        $this->assertInstanceOf(Collection::class, $result);
        
        foreach ($result as $outlet)
            $this->assertInstanceOf(Outlet::class, $outlet);
    }
}
