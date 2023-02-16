<?php

namespace Tests\Browser;

use Tests\DuskTestCase;

/**
 * @internal
 */
class WelcomeTest extends DuskTestCase
{
    /** @test */
    public function basicTest()
    {
        $this->browse(function ($browser) {
            $browser->visit('/')
                ->waitFor('.title', 1)
                ->assertSee('Laravel');
        });
    }
}
