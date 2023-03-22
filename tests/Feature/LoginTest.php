<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * @internal
 */
class LoginTest extends TestCase
{
    /** @var \App\Models\User */
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->artisan("passport:install");
    }

    /** @test */
    public function authenticate()
    {
        $this->postJson('/api/v1/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['access_token']);
    }

    /** @test */
    public function fetchTheCurrentUser()
    {
        $this->actingAs($this->user)
            ->getJson('/api/v1/auth/user')
            ->assertSuccessful()
            ->assertJsonStructure(['id', 'name', 'email']);
    }

    /** @test */
    public function logOut()
    {
        $token = $this->postJson('/api/v1/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ])->json()['access_token'];

        $this->postJson("/api/v1/logout?access_token={$token}")
            ->assertSuccessful();

        $this->getJson("/api/v1/user?access_token={$token}")
            ->assertStatus(401);
    }
}
