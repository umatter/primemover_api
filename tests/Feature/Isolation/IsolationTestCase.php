<?php

namespace Tests\Feature\Isolation;

use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/**
 * Shared fixtures for the per-user isolation suite.
 *
 * Every test in this directory works on the same shape the production database
 * has: two researchers, each owning exactly one experiment, with a crawler, a
 * run, a job and a report hanging off it -- and every one of those CHILD rows
 * stamped with the SERVICE ACCOUNT's user_id, because the runner and the upload
 * pipeline are what create them. Ownership therefore has to be derived from the
 * experiment; a per-row user_id check would show both researchers an experiment
 * containing nothing.
 *
 * A third tree belongs to the base TestCase's $this->user, so "sees own rows"
 * is distinguishable from "sees every row" in both directions: a scope that
 * matched everything and a scope that matched nothing both fail here.
 *
 * Deliberately NOT named *Test.php -- phpunit.xml collects by that suffix.
 */
abstract class IsolationTestCase extends TestCase
{
    /** The isolated caller most tests act as. */
    protected User $researcher;

    /** A second researcher, whose rows must stay invisible to the first. */
    protected User $otherResearcher;

    /** The machine caller: the runner, the orchestrator CLI, the e2e suite. */
    protected User $serviceAccount;

    /** @var array<string, Model> the researcher's experiment and its subtree */
    protected array $mine = [];

    /** @var array<string, Model> the other researcher's experiment and its subtree */
    protected array $theirs = [];

    /** @var array<string, Model> a third owner's subtree, present purely as noise */
    protected array $thirdParty = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->researcher = User::factory()->create();
        $this->otherResearcher = User::factory()->create();
        $this->serviceAccount = User::factory()->create();

        // The base TestCase creates $this->user first, so its id would collide
        // with the config default of [1]. Name the service account explicitly.
        config(['primemover.isolation.service_account_ids' => [$this->serviceAccount->id]]);

        $this->mine = $this->treeFor($this->researcher);
        $this->theirs = $this->treeFor($this->otherResearcher);
        $this->thirdParty = $this->treeFor($this->user);
    }

    /**
     * The five models isolation covers, keyed by their slot in $mine/$theirs.
     *
     * Used both as a PHPUnit data provider (one failure per model, so a broken
     * chain names itself) and as a plain list to loop over.
     *
     * @return array<string, array{0: string, 1: class-string<Model>}>
     */
    public static function ownedModels(): array
    {
        return [
            'experiment' => ['experiment', Experiment::class],
            'crawler' => ['crawler', Crawler::class],
            'queue' => ['queue', Queue::class],
            'job' => ['job', Job::class],
            'report' => ['report', Report::class],
        ];
    }

    /**
     * The four derived models -- everything except the ownership root.
     *
     * @return array<string, array{0: string, 1: class-string<Model>}>
     */
    public static function derivedModels(): array
    {
        $models = self::ownedModels();
        unset($models['experiment']);

        return $models;
    }

    protected function enableIsolation(): void
    {
        config(['primemover.isolation.enabled' => true]);
    }

    protected function disableIsolation(): void
    {
        config(['primemover.isolation.enabled' => false]);
    }

    /**
     * One experiment plus its whole subtree.
     *
     * $stampedWith is the user_id written onto the CHILD rows. It defaults to
     * the service account on purpose: that is what production looks like, and
     * it means every read assertion in this suite doubles as proof that
     * ownership is derived rather than read off the row.
     *
     * @return array<string, Model>
     */
    protected function treeFor(User $owner, ?int $stampedWith = null): array
    {
        $stamp = $stampedWith ?? $this->serviceAccount->id;

        $experiment = Experiment::factory()->create([
            'user_id' => $owner->id,
        ]);

        $crawler = Crawler::factory()->create([
            'user_id' => $stamp,
            'experiment_id' => $experiment->id,
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'configuration_id' => $this->configuration->id,
            'active' => 1,
            'testing' => 0,
        ]);

        // Flags pinned rather than left to the factory's randomElement, so the
        // runner's discovery queries (unprocessed / processed) are deterministic.
        $queue = Queue::factory()->create([
            'user_id' => $stamp,
            'crawler_id' => $crawler->id,
            'active' => 1,
            'new' => 1,
            'processed' => 0,
            'processing' => 0,
            'reviewed' => 0,
        ]);

        $job = Job::factory()->create([
            'user_id' => $stamp,
            'queue_id' => $queue->id,
            'active' => 1,
        ]);

        $report = Report::factory()->create([
            'user_id' => $stamp,
            'job_id' => $job->id,
        ]);

        return compact('experiment', 'crawler', 'queue', 'job', 'report');
    }

    /**
     * Ids the current caller may see, through the scope under test.
     *
     * @param class-string<Model> $model
     *
     * @return array<int, int>
     */
    protected function visibleIds(string $model): array
    {
        return $model::query()
            ->visibleTo()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Every id in the table, ignoring isolation.
     *
     * @param class-string<Model> $model
     *
     * @return array<int, int>
     */
    protected function allIds(string $model): array
    {
        return $model::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param iterable<Model|array|object> $rows
     *
     * @return array<int, int>
     */
    protected function idsOf(iterable $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $ids[] = (int) $row->id;
        }

        sort($ids);

        return $ids;
    }
}
