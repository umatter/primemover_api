<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

/**
 * The zone a queue's start_at is interpreted in.
 *
 * This is not decoration. `start_at` is stored as WALL CLOCK: the API keeps the
 * digits it is sent and discards the offset, verified against the live API --
 * "2030-07-15T03:00:00Z" is stored as 03:00, and "2030-07-15T05:00:00-05:00" as
 * 05:00. So this one setting decides when every scheduled run actually fires,
 * and changing it silently reinterprets every queue already in the table.
 *
 * It was never chosen. The config default was Europe/Berlin and APP_TIMEZONE was
 * unset on the deployed API, while the runner, the e2e, the spec DSL and the
 * canary all assume Europe/Zurich. Nothing broke, because the two share offsets
 * year-round -- which is a coincidence, and the kind that holds until someone
 * changes one of them for an unrelated reason.
 *
 * @coversNothing
 */
class AppTimezoneTest extends TestCase
{
    public function test_the_application_timezone_is_the_one_every_client_assumes(): void
    {
        $this->assertSame(
            'Europe/Zurich',
            config('app.timezone'),
            'the runner, the e2e, the spec DSL and the canary all send Europe/Zurich wall clock'
        );
    }

    public function test_the_fallback_does_not_disagree_with_it(): void
    {
        // Read from the file rather than config(), so an .env that happens to be
        // right cannot hide a default that is wrong.
        $config = file_get_contents(config_path('app.php'));

        $this->assertStringContainsString(
            "env('APP_TIMEZONE', 'Europe/Zurich')",
            $config,
            'a deployment without APP_TIMEZONE set must still land on the assumed zone'
        );
    }

    public function test_the_example_env_documents_it(): void
    {
        // The setting is invisible until it bites; an operator should meet it
        // while copying .env.example, not while diagnosing a run that fired an
        // hour early.
        $example = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_TIMEZONE=Europe/Zurich', $example);
    }
}
