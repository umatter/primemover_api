<?php

namespace Tests\Feature\Isolation;

use App\Access\Isolation;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Repository\ExperimentRepository;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Repository\ReportRepository;
use App\Rules\OwnedParent;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * Isolation OFF: the whole feature is inert.
 *
 * The flag ships disabled, so with it off an isolated researcher and the
 * service account must be indistinguishable -- same rows, same gate answers,
 * same validation. Anything else means the branch changed behaviour on a
 * running deployment before anyone opted in.
 *
 * @internal
 *
 * @coversDefaultClass \App\Access\Isolation
 */
class InertWhenDisabledTest extends IsolationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->disableIsolation();
    }

    /**
     * @covers ::bypasses
     * @covers ::enabled
     */
    public function test_every_caller_bypasses_while_the_flag_is_off(): void
    {
        $this->assertFalse(Isolation::enabled());

        $this->actingAs($this->researcher);
        $this->assertFalse(Isolation::isServiceAccount());
        $this->assertTrue(Isolation::bypasses(), 'A researcher must bypass isolation while the flag is off.');

        $this->actingAs($this->serviceAccount);
        $this->assertTrue(Isolation::bypasses());
    }

    /**
     * @dataProvider ownedModels
     *
     * @covers \App\Models\Concerns\ScopesToOwner::scopeVisibleTo
     */
    public function test_researcher_and_service_account_see_identical_rows(string $key, string $model): void
    {
        $this->actingAs($this->researcher);
        $asResearcher = $this->visibleIds($model);

        $this->actingAs($this->serviceAccount);
        $asServiceAccount = $this->visibleIds($model);

        $this->assertSame(
            $asServiceAccount,
            $asResearcher,
            "With isolation off a researcher and the service account must see the same {$model} rows."
        );

        $this->assertSame(
            $this->allIds($model),
            $asResearcher,
            "With isolation off the scope must not filter {$model} at all."
        );

        // The row set is not trivially empty, so "identical" is meaningful.
        $this->assertContains((int) $this->theirs[$key]->id, $asResearcher);
    }

    /**
     * @covers \App\Models\Concerns\ScopesToOwner::scopeVisibleTo
     */
    public function test_repositories_return_identical_result_sets(): void
    {
        $this->actingAs($this->researcher);
        $asResearcher = $this->repositorySnapshot();

        $this->actingAs($this->serviceAccount);
        $asServiceAccount = $this->repositorySnapshot();

        $this->assertSame($asServiceAccount, $asResearcher);

        foreach (['experiments', 'crawlers', 'queues', 'jobs', 'reports'] as $slot) {
            $this->assertNotEmpty($asResearcher[$slot], "The {$slot} snapshot is empty, so comparing it proves nothing.");
        }

        $this->assertContains((int) $this->theirs['experiment']->id, $asResearcher['experiments']);
        $this->assertContains((int) $this->theirs['crawler']->id, $asResearcher['crawlers']);
        $this->assertContains((int) $this->theirs['report']->id, $asResearcher['reports']);
    }

    /**
     * @dataProvider ownedModels
     */
    public function test_researcher_may_view_another_users_row(string $key, string $model): void
    {
        $this->actingAs($this->researcher);

        $this->assertTrue(
            Gate::allows('view', $this->theirs[$key]),
            "With isolation off a researcher must still be able to view another user's {$model}."
        );
    }

    public function test_researcher_may_still_update_and_delete_another_users_experiment(): void
    {
        $this->actingAs($this->researcher);

        $this->assertTrue(Gate::allows('update', $this->theirs['experiment']));
        $this->assertTrue(Gate::allows('delete', $this->theirs['experiment']));
    }

    public function test_researcher_may_administer_the_platform(): void
    {
        $this->actingAs($this->researcher);

        $this->assertTrue(Gate::allows('administer-platform'));
    }

    /**
     * @covers \App\Rules\OwnedParent::validate
     */
    public function test_owned_parent_accepts_another_users_parent(): void
    {
        $this->actingAs($this->researcher);

        $validator = Validator::make(
            ['experiment_id' => $this->theirs['experiment']->id],
            ['experiment_id' => [new OwnedParent(Experiment::class)]]
        );

        $this->assertTrue($validator->passes(), 'The re-parent guard must be inert while isolation is off.');
    }

    /**
     * Guests bypass too: every guarded route sits behind auth:api already, so
     * failing closed here would only create an outage path.
     *
     * @covers ::bypasses
     * @covers ::userId
     */
    public function test_unauthenticated_caller_bypasses(): void
    {
        $this->assertNull(Isolation::userId());
        $this->assertTrue(Isolation::bypasses());

        $this->enableIsolation();

        $this->assertTrue(Isolation::bypasses(), 'With nobody authenticated, isolation must fail open.');
        $this->assertSame($this->allIds(Experiment::class), $this->visibleIds(Experiment::class));
    }

    /**
     * The ?User hint on Gate::before is load-bearing, not tidiness: Gate skips
     * a before callback it cannot call with the current user, so a non-nullable
     * hint makes the bypass vanish for guests and every ability falls through
     * to a policy that denies. Several controller tests construct controllers
     * with nobody authenticated.
     */
    public function test_gate_still_allows_guests_while_isolation_is_on(): void
    {
        $this->enableIsolation();

        $this->assertTrue(
            Gate::allows('view', $this->theirs['experiment']),
            'Gate::before must still fire when there is no authenticated user.'
        );
        $this->assertTrue(Gate::allows('update', $this->theirs['experiment']));
        $this->assertTrue(Gate::allows('administer-platform'));
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function repositorySnapshot(): array
    {
        return [
            'experiments' => $this->idsOf((new ExperimentRepository(new Experiment()))->getData()->get()),
            'crawlers' => $this->idsOf((new CrawlerRepository(new Crawler()))->all(100)),
            'queues' => $this->idsOf((new QueueRepository(new Queue()))->all($this->theirs['crawler']->id, null)),
            'jobs' => $this->idsOf((new JobRepository(new Job()))->all($this->theirs['queue']->id, 100)),
            'reports' => $this->idsOf((new ReportRepository(new Report()))->all()),
        ];
    }
}
