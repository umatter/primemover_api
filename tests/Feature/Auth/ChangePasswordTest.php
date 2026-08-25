<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Http\Controllers\API\AuthController
 */
class ChangePasswordTest extends TestCase
{
    private const OLD = 'Old-password1';

    private const NEW = 'New-password2!';

    /**
     * @covers ::changePassword
     */
    public function test_changes_the_password_with_the_correct_current_one(): void
    {
        $user = $this->user();
        Passport::actingAs($user);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => self::OLD,
            'password' => self::NEW,
            'password_confirmation' => self::NEW,
        ])->assertOk();

        $this->assertTrue(Hash::check(self::NEW, $user->fresh()->password));
    }

    /**
     * @covers ::changePassword
     */
    public function test_rejects_a_wrong_current_password(): void
    {
        $user = $this->user();
        Passport::actingAs($user);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'not-it',
            'password' => self::NEW,
            'password_confirmation' => self::NEW,
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check(self::OLD, $user->fresh()->password));
    }

    /**
     * Same rule as reset-password: 8+, uppercase, digit, special char.
     *
     * @covers ::changePassword
     */
    public function test_rejects_a_weak_new_password(): void
    {
        Passport::actingAs($this->user());

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => self::OLD,
            'password' => 'alllowercase1',
            'password_confirmation' => 'alllowercase1',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    /**
     * @covers ::changePassword
     */
    public function test_requires_authentication(): void
    {
        $this->withExceptionHandling();
        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => self::OLD,
            'password' => self::NEW,
            'password_confirmation' => self::NEW,
        ])->assertStatus(401);
    }

    private function user(): User
    {
        // The base TestCase disables exception handling; these tests assert on
        // the HTTP responses (422 / 401), so turn it back on.
        $this->withExceptionHandling();

        return User::factory()->create(['password' => Hash::make(self::OLD)]);
    }
}
