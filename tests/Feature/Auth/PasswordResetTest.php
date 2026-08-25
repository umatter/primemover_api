<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Http\Controllers\API\AuthController
 */
class PasswordResetTest extends TestCase
{
    /**
     * @covers ::forgotPassword
     */
    public function test_reset_link_points_at_the_frontend(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://gui.example.org']);
        $user = User::factory()->create(['email' => 'reset@example.org']);

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['status' => true]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://gui.example.org/password/reset/')
                && str_ends_with($url, '?email=reset%40example.org');
        });
    }

    /**
     * @covers ::forgotPassword
     */
    public function test_unknown_email_is_rejected_without_sending(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/forgot-password', ['email' => 'nobody@example.org'])
            ->assertStatus(400)
            ->assertJson(['status' => false]);

        Notification::assertNothingSent();
    }

    /**
     * A dead relay used to surface as a 500 with a stack trace (APP_DEBUG) --
     * indistinguishable, to the user, from a wrong address.
     *
     * @covers ::forgotPassword
     */
    public function test_unreachable_mailer_is_reported_as_service_unavailable(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);
        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])
            ->assertStatus(503)
            ->assertJsonMissing(['exception'])
            ->assertJson(['status' => false, 'message' => 'The password reset email could not be sent. Please contact an administrator.']);
    }
}
