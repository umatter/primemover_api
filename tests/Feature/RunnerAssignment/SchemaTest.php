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
     * @covers ::isRunner
     */
    public function test_isolation_knows_the_default_runner_and_the_runner_accounts(): void
    {
        $this->assertSame($this->defaultRunner->id, Isolation::defaultRunnerUserId());
        $this->assertTrue(Isolation::isRunner($this->defaultRunner));
        $this->assertTrue(Isolation::isRunner($this->secondRunner));
        $this->assertFalse(Isolation::isRunner($this->researcher));
    }

    public function test_default_runner_falls_back_to_user_one_when_unset(): void
    {
        config(['primemover.runner.default_user_id' => null]);
        $this->assertSame(1, Isolation::defaultRunnerUserId());
    }
}
