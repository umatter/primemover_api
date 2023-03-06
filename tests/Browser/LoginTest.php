<?php

namespace Tests\Browser;

use App\Models\User;
use Tests\Browser\Pages\Home;
use Tests\Browser\Pages\Login;
use Tests\DuskTestCase;

/**
 * @internal
 */
class LoginTest extends DuskTestCase
{
    public function setUp(): void
    {
        parent::setup();

        static::closeAll();
    }

    /** @test */
    public function loginWithValidCredentials()
    {
        $user = factory(User::class)->create();

        $this->browse(function ($browser) use ($user) {
            $browser->visit(new Login())
                ->submit($user->email, 'password')
                ->assertPageIs(Home::class);
        });
    }

    /** @test */
    public function loginWithInvalidCredentials()
    {
        $this->browse(function ($browser) {
            $browser->visit(new Login())
                ->submit('test@test.app', 'password')
                ->assertSee('These credentials do not match our records.');
        });
    }

    /** @test */
    public function logOutTheUser()
    {
        $user = factory(User::class)->create();

        $this->browse(function ($browser) use ($user) {
            $browser->visit(new Login())
                ->submit($user->email, 'password')
                ->on(new Home())
                ->clickLogout()
                ->assertPageIs(Login::class);
        });
    }
}
