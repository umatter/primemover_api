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
use Illuminate\Support\Facades\Gate;

/**
 * Isolation ON: the service account still sees everything.
 *
 * This is the guard that keeps the platform running. The Java runner, the
 * orchestrator CLI, the MCP server and the e2e suite all authenticate as the
 * same machine account, so the moment it stops seeing other users' rows it
 * stops discovering work -- silently, since an empty crawler list is
 * indistinguishable from "nothing to do".
 *
 * @internal
 *
 * @coversDefaultClass \App\Access\Isolation
 */
class ServiceAccountTest extends IsolationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->enableIsolation();
        $this->actingAs($this->serviceAccount);
    }

    /**
     * @dataProvider ownedModels
     *
     * @covers \App\Models\Concerns\ScopesToOwner::scopeVisibleTo
     * @covers ::bypasses
     * @covers ::isServiceAccount
     */
    public function testServiceAccountSeesRowsOwnedByEveryUser(string $key, string $model): void
    {
        $visible = $this->visibleIds($model);

        $this->assertSame(
            $this->allIds($model),
            $visible,
            "The service account must see every {$model} row, whoever owns it."
        );

        $this->assertContains((int) $this->mine[$key]->id, $visible);
        $this->assertContains((int) $this->theirs[$key]->id, $visible);
        $this->assertContains((int) $this->thirdParty[$key]->id, $visible);
    }

    /**
     * @covers ::bypasses
     */
    public function testIsolationReportsTheServiceAccountAsBypassing(): void
    {
        $this->assertTrue(Isolation::enabled());
        $this->assertTrue(Isolation::isServiceAccount());
        $this->assertTrue(Isolation::bypasses());
    }

    /**
     * The repositories, not just the scope: a dropped ->visibleTo() would pass
     * the scope tests above and still break here if it were ever inverted.
     *
     * @covers \App\Models\Concerns\ScopesToOwner::scopeVisibleTo
     */
    public function testRepositoriesStillReturnEveryUsersRows(): void
    {
        $experiments = $this->idsOf((new ExperimentRepository(new Experiment()))->getData()->get());
        $crawlers = $this->idsOf((new CrawlerRepository(new Crawler()))->all(100));
        $reports = $this->idsOf((new ReportRepository(new Report()))->all());

        foreach ([$this->mine, $this->theirs, $this->thirdParty] as $tree) {
            $this->assertContains((int) $tree['experiment']->id, $experiments);
            $this->assertContains((int) $tree['crawler']->id, $crawlers);
            $this->assertContains((int) $tree['report']->id, $reports);

            // Filtered by parent id so the assertion cannot be satisfied (or
            // defeated) by pagination.
            $queues = $this->idsOf((new QueueRepository(new Queue()))->all($tree['crawler']->id, null));
            $jobs = $this->idsOf((new JobRepository(new Job()))->all($tree['queue']->id, 100));

            $this->assertSame([(int) $tree['queue']->id], $queues);
            $this->assertSame([(int) $tree['job']->id], $jobs);
        }
    }

    /**
     * The runner's actual discovery query.
     *
     * @covers \App\Models\Concerns\ScopesToOwner::scopeVisibleTo
     */
    public function testRunnerDiscoveryStillFindsEveryUsersUnprocessedCrawlers(): void
    {
        $unprocessed = $this->idsOf((new CrawlerRepository(new Crawler()))->unprocessed());

        $this->assertContains((int) $this->mine['crawler']->id, $unprocessed);
        $this->assertContains((int) $this->theirs['crawler']->id, $unprocessed);
        $this->assertContains((int) $this->thirdParty['crawler']->id, $unprocessed);
    }

    /**
     * @dataProvider ownedModels
     */
    public function testServiceAccountIsAllowedEveryAbilityOnAnotherUsersRow(string $key, string $model): void
    {
        $row = $this->theirs[$key];

        $this->assertTrue(Gate::allows('view', $row), "view denied on a {$model} the service account does not own");
        $this->assertTrue(Gate::allows('update', $row), "update denied on a {$model} the service account does not own");
        $this->assertTrue(Gate::allows('delete', $row), "delete denied on a {$model} the service account does not own");
    }

    public function testServiceAccountMayAdministerThePlatform(): void
    {
        $this->assertTrue(Gate::allows('administer-platform'));
    }

    public function testResearcherMayNotAdministerThePlatform(): void
    {
        $this->actingAs($this->researcher);

        $this->assertTrue(Gate::denies('administer-platform'));
    }
}
