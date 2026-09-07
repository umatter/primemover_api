<?php

namespace Tests\Feature\Modules\Job\Model;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The migration rolls back cleanly.
 *
 * A down() that does not work is a migration nobody dares run, and this one
 * adds two indexes as well as four columns -- dropping the columns without
 * dropping the indexes first fails on MySQL.
 *
 * @internal
 *
 * @coversNothing
 */
class JobDecisionMigrationTest extends TestCase
{
    public function test_the_decision_columns_roll_back_and_forward(): void
    {
        $migration = require database_path('migrations/2026_09_07_000001_add_decision_columns_to_jobs_table.php');

        $migration->down();

        foreach (['decision_degraded', 'decision_treatment_lost', 'decision_selection_mode', 'decision_report'] as $column) {
            $this->assertFalse(Schema::hasColumn('jobs', $column), "jobs.{$column} survived down()");
        }

        $migration->up();

        foreach (['decision_degraded', 'decision_treatment_lost', 'decision_selection_mode', 'decision_report'] as $column) {
            $this->assertTrue(Schema::hasColumn('jobs', $column), "jobs.{$column} did not come back");
        }
    }
}
