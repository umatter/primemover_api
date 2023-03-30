<?php

namespace Tests\Feature\Modules\Outlet\Service;

use App\Models\User;
use App\Modules\Outlet\Model\Outlet;
use App\Modules\Outlet\Service\OutletService;
use Illuminate\Database\Eloquent\Collection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class OutletServiceTest extends TestCase
{
    /** @test */
    public function testGetData(): void
    {
        $service = new OutletService(new Outlet());
        $result = $service->getData();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $outlet) {
            unset($outlet->user, $outlet->updated_at, $outlet->created_at);

            $this->assertInstanceOf(Outlet::class, $outlet);
            $this->assertDatabaseHas('outlets', $outlet->toArray());
        }
    }

    /** @test */
    public function testGetById(): void
    {
        $outlet = m::mock(Outlet::class);
        $outlet->allows('getAttribute')->with('id')->andReturns(1);
        $outlet->allows('with')->with(['user'])->andReturns(new User([
            'id' => 1,
            'name' => 'user name',
            'email' => 'user@example.com',
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        ]));
        $outlet->allows('findOrFail')->andReturns(new Outlet([
            'id' => 1,
            'title' => 'outlet title',
            'outlet' => 'outlet',
        ]));

        $service = new OutletService(new Outlet());
        $result = $service->getById($outlet->id);

        $this->assertInstanceOf(Outlet::class, $result);
        $this->assertInstanceOf(User::class, $result->user);
        $this->assertEquals($outlet->id, $result->id);
    }

    /** @test */
    public function testSave(): void
    {
        $data = [
            'title' => 'outlet title',
            'outlet' => 'outlet',
        ];

        $service = new OutletService(new Outlet());
        $result = $service->save($data);

        $this->assertDatabaseHas('outlets', $data);
        $this->assertInstanceOf(Outlet::class, $result);
    }

    /** @test */
    public function testUpdate(): void
    {
        $data = [
            'title' => 'outlet title updated',
            'outlet' => 'outlet update',
        ];

        $outletMock = m::mock(Outlet::class);
        $outletMock->allows('firstOrFail')->andReturns(new Outlet([
            'id' => 1,
            'title' => 'outlet title',
            'outlet' => 'outlet',
        ]));
        $outletMock->allows('getAttribute')->with(['title', 'outlet'])->andReturns(['title' => 'outlet title', 'outlet' => 'outlet']);
        $outletMock->allows('toArray')->andReturns($data);
        $outlet = $outletMock->firstOrFail();

        $outletMock->allows('update')->andReturnUsing(function () use ($outlet, $data) {
            $outlet->title = $data['title'];
            $outlet->outlet = $data['outlet'];

            return true;
        });

        $service = new OutletService(new Outlet());
        $result = $service->update($data, $outletMock);

        $this->assertInstanceOf(Outlet::class, $result);
        $this->assertEquals($data, $outletMock->toArray());
    }

    /** @test */
    public function testDelete(): void
    {
        $outlet = Outlet::create([
            'user_id' => $this->user->id,
            'title' => 'outlet title',
            'outlet' => 'outlet',
        ]);

        $service = new OutletService(new Outlet());
        $result = $service->delete($outlet);

        $this->assertDatabaseMissing('outlets', $outlet->toArray());
        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $outlet) {
            $this->assertInstanceOf(Outlet::class, $outlet);
        }
    }
}
