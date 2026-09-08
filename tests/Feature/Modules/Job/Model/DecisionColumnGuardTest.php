<?php

namespace Tests\Feature\Modules\Job\Model;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Service\ExperimentHealth;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The decision rollups must not depend on the migration having run yet.
 *
 * The API source is BIND-MOUNTED into the running php container on ex63, so new
 * code is live the instant it lands on disk while `php artisan migrate` is a
 * separate, later command. Every rollup this change set added --
 * Crawler::$withCount's latest_queues_treatment_lost, Queue's
 * jobs_degraded_count / jobs_treatment_lost_count, ExperimentHealth's two
 * decision sums -- reads a jobs.decision_* column that does not exist until
 * that command runs. Referencing it before then is SQLSTATE[42S22] and an HTTP
 * 500, and because the rollup rides on Model::newQuery() the blast radius
 * includes GET /crawlers-unprocessed: the runner's ONLY work feed, polled every
 * 60 seconds. That combination already took production down for 31 minutes. No
 * deploy ordering can close the window on a bind mount; only the code can.
 *
 * The other half of the contract is what happens instead of the 500: the counts
 * are OMITTED, never emitted as 0. `latest_queues_treatment_lost: 0` and
 * `treatment_lost_jobs: 0` are the positive claim "no run here lost its
 * treatment" -- a statement about the science of a study that nobody has
 * measured. Absent means unknown (docs/integration-contract.md 7.3), and every
 * consumer already has to handle a missing field.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Model\Job
 */
class DecisionColumnGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * The one that matters: the runner's work poll.
     *
     * GET /crawlers-unprocessed goes through CrawlerRepository::unprocessed(),
     * i.e. Crawler::query() -> newQuery(), which is exactly where Laravel
     * applies $withCount. If the rollup is unconditional, this 500s and the
     * whole fleet stops fetching work.
     *
     * @covers ::hasDecisionColumns
     * @covers \App\Modules\Crawler\Model\Crawler::latestQueuesWithCount
     */
    public function test_the_runner_work_feed_answers_without_the_decision_columns(): void
    {
        $this->aRunWithALostTreatment();
        $this->withoutDecisionColumns();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/crawlers-unprocessed')
            ->assertStatus(200);

        $crawler = $this->crawlerNamed($response->json('data'), $this->crawler->id);

        $this->assertNotNull($crawler, 'the work feed returned no crawlers at all');
        $this->assertArrayNotHasKey(
            'latest_queues_treatment_lost',
            $crawler,
            'an unmigrated database reported a treatment-lost count; absent means unknown, 0 means "nothing lost"'
        );
        $this->assertArrayHasKey('latest_queues_failed', $crawler, 'the status-only rollups must survive');
    }

    /**
     * @covers ::hasDecisionColumns
     * @covers \App\Modules\Crawler\Model\Crawler::latestQueuesAttributes
     */
    public function test_the_crawler_list_and_show_answer_without_the_decision_columns(): void
    {
        $this->aRunWithALostTreatment();
        $this->withoutDecisionColumns();

        $list = $this->actingAs($this->user)->getJson('/api/v1/crawlers')->assertStatus(200);
        $row = $this->crawlerNamed($list->json('data'), $this->crawler->id);

        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('latest_queues_treatment_lost', $row);
        $this->assertArrayHasKey('latest_queues_degraded', $row);

        $show = $this->actingAs($this->user)
            ->getJson('/api/v1/crawlers/'.$this->crawler->id)
            ->assertStatus(200);

        $this->assertArrayNotHasKey('latest_queues_treatment_lost', $show->json('data'));
    }

    /**
     * The eight queues-* list methods, GET /queues and GET /queues/{id} all go
     * through Queue::jobOutcomeCountRelations().
     *
     * @covers ::hasDecisionColumns
     * @covers \App\Modules\Queue\Model\Queue::jobOutcomeCountRelations
     */
    public function test_the_queue_endpoints_answer_without_the_decision_columns(): void
    {
        $run = $this->aRunWithALostTreatment();
        $run->update(['processed' => 1, 'reviewed' => 1]);

        $this->withoutDecisionColumns();

        foreach ([
            '/api/v1/queues',
            '/api/v1/queues/'.$run->id,
            '/api/v1/queues-processed',
            '/api/v1/queues-unprocessed',
            '/api/v1/queues-reviewed',
            '/api/v1/queues-unreviewed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-processed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-unprocessed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-reviewed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-unreviewed',
        ] as $url) {
            $body = $this->actingAs($this->user)->getJson($url)->assertStatus(200)->json('data');

            $rows = array_is_list((array) $body) ? $body : [$body];

            foreach ($rows as $queue) {
                $this->assertArrayNotHasKey('jobs_treatment_lost_count', $queue, $url);
                $this->assertArrayNotHasKey('jobs_degraded_count', $queue, $url);
                $this->assertArrayHasKey('jobs_failed_count', $queue, $url);

                // The advertised rollup and the served rollup agree, so a
                // caller stripping a serialised queue back to real columns
                // strips exactly the keys that are there.
                foreach (Queue::jobOutcomeCountAttributes() as $rollup) {
                    $this->assertArrayHasKey($rollup, $queue, $url);
                }
            }
        }
    }

    /**
     * @covers ::hasDecisionColumns
     * @covers \App\Modules\Experiment\Service\ExperimentHealth::for
     */
    public function test_experiment_health_omits_the_decision_counts_rather_than_reporting_zero(): void
    {
        $this->aRunWithALostTreatment();
        $this->withoutDecisionColumns();

        $health = $this->actingAs($this->user)
            ->getJson('/api/v1/experiments/'.$this->experiment->id.'/health')
            ->assertStatus(200)
            ->json('data');

        $this->assertArrayNotHasKey('treatment_lost_jobs', $health);
        $this->assertArrayNotHasKey('degraded_jobs', $health);

        // Everything that does not depend on the migration is still reported.
        $this->assertArrayHasKey('bad_jobs', $health);
        $this->assertArrayHasKey('runs', $health);
        $this->assertArrayHasKey('last_finished_at', $health);
    }

    /**
     * A crawler read in PHP rather than serialised must not answer 0 either.
     *
     * @covers ::hasDecisionColumns
     * @covers \App\Modules\Crawler\Model\Crawler::getLatestQueuesTreatmentLostAttribute
     */
    public function test_the_crawler_accessor_answers_unknown_not_zero(): void
    {
        $this->aRunWithALostTreatment();
        $this->withoutDecisionColumns();

        $crawler = Crawler::query()->findOrFail($this->crawler->id);

        $this->assertNull(
            $crawler->latest_queues_treatment_lost,
            '0 would assert that nothing in the window lost its treatment'
        );
        $this->assertSame(1, $crawler->latest_queues_count);
        $this->assertArrayNotHasKey('latest_queues_treatment_lost', $crawler->toArray());
    }

    /**
     * The guard must not cost the counts on a database that HAS the columns.
     *
     * @covers ::hasDecisionColumns
     */
    public function test_with_the_columns_present_the_counts_appear_and_are_correct(): void
    {
        $run = $this->aRunWithALostTreatment();

        $crawler = $this->crawlerNamed(
            $this->actingAs($this->user)->getJson('/api/v1/crawlers')->assertStatus(200)->json('data'),
            $this->crawler->id
        );

        $this->assertSame(1, $crawler['latest_queues_treatment_lost']);

        $queue = $this->actingAs($this->user)
            ->getJson('/api/v1/queues/'.$run->id)
            ->assertStatus(200)
            ->json('data');

        $this->assertSame(1, $queue['jobs_treatment_lost_count']);
        $this->assertSame(2, $queue['jobs_degraded_count']);

        $health = $this->actingAs($this->user)
            ->getJson('/api/v1/experiments/'.$this->experiment->id.'/health')
            ->assertStatus(200)
            ->json('data');

        $this->assertSame(1, $health['treatment_lost_jobs']);
        $this->assertSame(2, $health['degraded_jobs']);
    }

    /**
     * The compiled SQL must not so much as NAME the missing columns.
     *
     * This is the assertion that speaks for production. The suite runs on
     * sqlite, where a double-quoted identifier that resolves to no column is
     * silently reinterpreted as a STRING LITERAL -- so `"decision_treatment_lost"
     * = 1` there is merely always false and the endpoint answers 200 with a
     * fabricated 0. MySQL 8 answers the same statement with SQLSTATE[42S22]
     * 1054 Unknown column and the request becomes an HTTP 500. Neither
     * behaviour is acceptable and only one of them is visible to this suite,
     * so the test asserts on the text of the query instead of on its result:
     * a column that is never mentioned cannot be misread by either engine.
     *
     * @covers ::hasDecisionColumns
     * @covers \App\Modules\Crawler\Model\Crawler::latestQueuesWithCount
     * @covers \App\Modules\Queue\Model\Queue::jobOutcomeCountRelations
     */
    public function test_no_query_names_a_column_the_database_does_not_have(): void
    {
        $this->withoutDecisionColumns();

        $crawlers = Crawler::query()->toSql();

        $this->assertStringNotContainsString('decision_treatment_lost', $crawlers);
        $this->assertStringContainsString('latest_queues_failed', $crawlers, 'the status rollups must survive');

        $queues = Queue::query()->withJobOutcomeCounts()->toSql();

        $this->assertStringNotContainsString('decision_treatment_lost', $queues);
        $this->assertStringNotContainsString('decision_degraded', $queues);
        $this->assertStringContainsString('jobs_failed_count', $queues, 'the status rollups must survive');
    }

    /**
     * One schema probe for the whole request, not one per crawler row.
     *
     * Schema::hasColumn is a query of its own (pragma table_info on sqlite,
     * information_schema on MySQL). Asking it inside a rollup that is built per
     * row would trade a 500 for a query storm on the very endpoints the rollup
     * was introduced to make cheap.
     *
     * @covers ::hasDecisionColumns
     */
    public function test_the_capability_check_is_resolved_once_not_per_row(): void
    {
        Crawler::factory()->count(9)->create([
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'experiment_id' => $this->experiment->id,
            'configuration_id' => $this->configuration->id,
        ]);

        Job::forgetDecisionColumnSupport();

        $probes = 0;
        DB::listen(function ($query) use (&$probes): void {
            $sql = mb_strtolower($query->sql);

            if (str_contains($sql, 'table_info') || str_contains($sql, 'information_schema')) {
                $probes++;
            }
        });

        $rows = $this->actingAs($this->user)->getJson('/api/v1/crawlers')->assertStatus(200)->json('data');

        $this->assertGreaterThan(1, count($rows), 'the page has to hold several rows for this to mean anything');
        $this->assertSame(1, $probes, "the schema capability check ran {$probes} times for one request");
    }

    /**
     * The service reports the counts as absent, not as an all-clear.
     *
     * @covers \App\Modules\Experiment\Service\ExperimentHealth::for
     */
    public function test_the_health_service_itself_drops_the_keys(): void
    {
        $this->aRunWithALostTreatment();

        $withColumns = (new ExperimentHealth())->for($this->experiment);
        $this->assertSame(1, $withColumns['treatment_lost_jobs']);

        $this->withoutDecisionColumns();

        $withoutColumns = (new ExperimentHealth())->for($this->experiment);

        $this->assertArrayNotHasKey('treatment_lost_jobs', $withoutColumns);
        $this->assertArrayNotHasKey('degraded_jobs', $withoutColumns);
    }

    /**
     * Roll the decision migration back under the running code, exactly as a
     * checkout-before-migrate deploy leaves the database.
     */
    private function withoutDecisionColumns(): void
    {
        // The migration's own down(), so the two can never drift; it also
        // forgets the memo, which is the behaviour being relied on here.
        $migration = require database_path('migrations/2026_09_07_000001_add_decision_columns_to_jobs_table.php');
        $migration->down();

        // Deliberately Schema::hasColumn and not Job::hasDecisionColumns():
        // this assertion has to hold whether or not the guard exists, so that
        // an unguarded build fails on the 500 it really produces rather than
        // on a missing helper.
        $this->assertFalse(
            Schema::hasColumn('jobs', 'decision_treatment_lost'),
            'the columns were not actually dropped'
        );
    }

    /**
     * A run inside the rollup window holding one treatment-lost job and two
     * degraded ones.
     */
    private function aRunWithALostTreatment(): Queue
    {
        $run = Queue::factory()->create([
            'crawler_id' => $this->crawler->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_SUCCESS,
            'started_at' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
            'processed' => 0,
            'reviewed' => 0,
        ]);

        Job::factory()->create([
            'queue_id' => $run->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_degraded' => true,
            'decision_treatment_lost' => true,
        ]);

        Job::factory()->create([
            'queue_id' => $run->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_degraded' => true,
            'decision_treatment_lost' => false,
        ]);

        return $run;
    }

    /**
     * @param  array<int, array<string, mixed>> $rows
     * @return null|array<string, mixed>
     */
    private function crawlerNamed(array $rows, int $id): ?array
    {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }
}
