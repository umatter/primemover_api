<?php

namespace Tests\Feature\RunnerAssignment;

use App\Access\Isolation;
use Illuminate\Support\Facades\Schema;

/**
 * @internal
 *
 * @coversDefaultClass \App\Access\Isolation
 */
class SchemaTest extends RunnerAssignmentTestCase
{
    public function test_experiments_have_a_nullable_runner_assignment(): void
    {
        $this->assertTrue(Schema::hasColumn('experiments', 'runner_user_id'));
        $this->assertNull($this->unassigned['experiment']->fresh()->runner_user_id);
        $this->assertTrue($this->forSecond['experiment']->fresh()->runner->is($this->secondRunner));
    }

    /**
     * @covers ::defaultRunnerUserId
     */
    public function test_isolation_knows_the_default_runner(): void
    {
        $this->assertSame($this->defaultRunner->id, Isolation::defaultRunnerUserId());
    }

    public function test_default_runner_falls_back_to_user_one_when_unset(): void
    {
        config(['primemover.runner.default_user_id' => null]);
        $this->assertSame(1, Isolation::defaultRunnerUserId());
    }

    /**
     * default_user_id must itself be a configured runner account -- otherwise,
     * with isolation on, the default runner is partitioned to see nothing and
     * the platform stops with an empty work list, silently.
     *
     * @covers ::defaultRunnerUserId
     * @covers ::serviceAccountIds
     */
    public function test_default_runner_is_a_configured_runner_account(): void
    {
        $this->assertTrue(in_array(Isolation::defaultRunnerUserId(), Isolation::serviceAccountIds(), true));
    }
}
