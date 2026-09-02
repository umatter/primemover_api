<?php

namespace Tests\Feature\RunnerAssignment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\Feature\Isolation\IsolationTestCase;

/**
 * Fixtures for runner assignment: two runner accounts (both service accounts,
 * one of them the configured default) and a researcher who owns experiments
 * assigned to each runner and one left unassigned (NULL = default runner).
 *
 * Reuses IsolationTestCase's tree builder so the crawler/queue flags match what
 * the runner's discovery queries select on.
 */
abstract class RunnerAssignmentTestCase extends IsolationTestCase
{
    protected User $defaultRunner;

    protected User $secondRunner;

    /** @var array<string, Model> researcher-owned, runner_user_id = NULL */
    protected array $unassigned = [];

    /** @var array<string, Model> researcher-owned, runner_user_id = defaultRunner */
    protected array $forDefault = [];

    /** @var array<string, Model> researcher-owned, runner_user_id = secondRunner */
    protected array $forSecond = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultRunner = $this->serviceAccount;
        $this->secondRunner = User::factory()->create();

        config([
            'primemover.isolation.service_account_ids' => [
                $this->defaultRunner->id,
                $this->secondRunner->id,
            ],
            'primemover.runner.default_user_id' => $this->defaultRunner->id,
        ]);

        // $this->mine (from the parent) is the researcher's unassigned tree.
        $this->unassigned = $this->mine;

        $this->forDefault = $this->treeFor($this->researcher);
        $this->forDefault['experiment']->update(['runner_user_id' => $this->defaultRunner->id]);

        $this->forSecond = $this->treeFor($this->researcher);
        $this->forSecond['experiment']->update(['runner_user_id' => $this->secondRunner->id]);
    }
}
