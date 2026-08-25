<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Administrative fallback for when the reset email cannot be delivered.
 *
 * The password is prompted for, never taken as an argument: an argument lands
 * in the shell history, and a one-liner through `tinker` has already bitten
 * once -- PHP's double-quoted string interpolated a `$` in the password, so
 * what got stored was not what was typed, with no error anywhere.
 */
class SetUserPassword extends Command
{
    protected $signature = 'user:set-password {email : The account to update}';

    protected $description = 'Set a user\'s password interactively (bypasses the email reset flow)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (null === $user) {
            $this->error("No user with email {$this->argument('email')}");

            return self::FAILURE;
        }

        $password = $this->secret('New password');
        $confirm = $this->secret('Confirm new password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match');

            return self::FAILURE;
        }

        if (mb_strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters');

            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated for {$user->email}");

        return self::SUCCESS;
    }
}
