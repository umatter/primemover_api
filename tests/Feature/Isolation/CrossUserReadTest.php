<?php

namespace Tests\Feature\Isolation;

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
 * Isolation ON: one researcher cannot read another researcher's rows.
 *
 * The fixture gives the acting researcher exactly one row per model, so every
 * assertion here is an exact set comparison. That matters: a broken ownership
 * chain fails in one of two silent ways -- matching every row or matching none
 * -- and a "does not contain the other user's id" check would pass for the
 * second. Both are caught by asserting the visible set equals [own id].
 *
 * @internal
 *
 * @coversDefaultClass \App\Models\Concerns\ScopesToOwner
 */
class CrossUserReadTest extends IsolationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->enableIsolation();
        $this->actingAs($this->researcher);
    }

    /**
     * @dataProvider ownedModels
     *
     * @covers ::scopeVisibleTo
     */
    public function testResearcherSeesOnlyTheirOwnRows(string $key, string $model): void
    {
        $visible = $this->visibleIds($model);

        $this->assertSame(
            [(int) $this->mine[$key]->id],
            $visible,
            "A researcher must see exactly their own {$model} -- no more (leak), no less (blind)."
        );

        $this->assertNotContains((int) $this->theirs[$key]->id, $visible);
        $this->assertNotContains((int) $this->thirdParty[$key]->id, $visible);
    }

    /**
     * Jobs walk queue -> crawler -> experiment, skipping the crawler_id column
     * the jobs table does not have. Spelled out separately from the provider
     * because this is the chain most likely to be "fixed" into a wrong shape.
     *
     * @covers ::scopeVisibleTo
     */
    public function testJobChainWalksTheQueueNotTheCrawler(): void
    {
        $this->assertSame('queue.crawler.experiment', Job::OWNERSHIP_PATH);

        // jobs has no crawler_id, so this relation is structurally dead.
        $this->assertNull($this->mine['job']->crawler);

        $visible = $this->visibleIds(Job::class);

        $this->assertSame([(int) $this->mine['job']->id], $visible);
        $this->assertGreaterThan(
            count($visible),
            count($this->allIds(Job::class)),
            'The fixture must contain jobs the researcher cannot see, or this proves nothing.'
        );
    }

    /**
     * Reports walk the longest chain of all: job -> queue -> crawler ->
     * experiment.
     *
     * @covers ::scopeVisibleTo
     */
    public function testReportChainReachesTheExperimentThroughTheJob(): void
    {
        $this->assertSame('job.queue.crawler.experiment', Report::OWNERSHIP_PATH);

        $visible = $this->visibleIds(Report::class);

        $this->assertSame([(int) $this->mine['report']->id], $visible);
        $this->assertNotContains((int) $this->theirs['report']->id, $visible);
        $this->assertGreaterThan(
            count($visible),
            count($this->allIds(Report::class)),
            'The fixture must contain reports the researcher cannot see, or this proves nothing.'
        );
    }

    /**
     * @covers ::scopeVisibleTo
     */
    public function testRepositoriesHideTheOtherResearchersRows(): void
    {
        $experiments = $this->idsOf((new ExperimentRepository(new Experiment()))->getData()->get());
        $this->assertSame([(int) $this->mine['experiment']->id], $experiments);

        $experimentList = $this->idsOf((new ExperimentRepository(new Experiment()))->all());
        $this->assertSame([(int) $this->mine['experiment']->id], $experimentList);

        $crawlers = $this->idsOf((new CrawlerRepository(new Crawler()))->all(100));
        $this->assertSame([(int) $this->mine['crawler']->id], $crawlers);

        $reports = $this->idsOf((new ReportRepository(new Report()))->all());
        $this->assertSame([(int) $this->mine['report']->id], $reports);

        // Asked for BY PARENT ID: the researcher names the other researcher's
        // crawler and run directly, which is the interesting attack shape, and
        // it also takes pagination out of the picture.
        $ownQueues = (new QueueRepository(new Queue()))->all($this->mine['crawler']->id, null);
        $theirQueues = (new QueueRepository(new Queue()))->all($this->theirs['crawler']->id, null);
        $this->assertSame([(int) $this->mine['queue']->id], $this->idsOf($ownQueues));
        $this->assertSame([], $this->idsOf($theirQueues));

        $ownJobs = (new JobRepository(new Job()))->all($this->mine['queue']->id, 100);
        $theirJobs = (new JobRepository(new Job()))->all($this->theirs['queue']->id, 100);
        $this->assertSame([(int) $this->mine['job']->id], $this->idsOf($ownJobs));
        $this->assertSame([], $this->idsOf($theirJobs));
    }

    /**
     * @dataProvider ownedModels
     */
    public function testViewIsDeniedOnAnotherResearchersRow(string $key, string $model): void
    {
        $this->assertTrue(
            Gate::denies('view', $this->theirs[$key]),
            "A researcher must not be able to view another user's {$model}."
        );

        $this->assertTrue(
            Gate::allows('view', $this->mine[$key]),
            "A researcher must still be able to view their own {$model}."
        );
    }

    public function testShowingAnotherResearchersExperimentOverHttpIsForbidden(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->researcher)
            ->getJson('/api/v1/experiments/'.$this->theirs['experiment']->id)
            ->assertForbidden();

        $this->actingAs($this->researcher)
            ->getJson('/api/v1/experiments/'.$this->mine['experiment']->id)
            ->assertSuccessful();
    }

    public function testShowingAnotherResearchersCrawlerOverHttpIsForbidden(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->researcher)
            ->getJson('/api/v1/crawlers/'.$this->theirs['crawler']->id)
            ->assertForbidden();

        $this->actingAs($this->researcher)
            ->getJson('/api/v1/crawlers/'.$this->mine['crawler']->id)
            ->assertSuccessful();
    }
}
