<?php

namespace Tests\Feature\Modules\Queue\Model;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The canonical status vocabulary and the per-queue job rollup.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Model\Queue
 */
class QueueStatusTest extends TestCase
{
    /**
     * @covers ::normalizeStatus
     */
    public function test_normalize_status_maps_the_legacy_spellings(): void
    {
        $this->assertSame(Queue::STATUS_SKIPPED, Queue::normalizeStatus('skpped'));
        $this->assertSame(Queue::STATUS_FAILURE, Queue::normalizeStatus('failed'));
        $this->assertSame(Queue::STATUS_FAILURE, Queue::normalizeStatus('  FAILURE '));
        $this->assertSame(Queue::STATUS_WARNING, Queue::normalizeStatus('warning'));
        $this->assertSame('', Queue::normalizeStatus(null));
    }

    /**
     * An unrecognised code must stay unrecognised. Mapping it onto success is
     * how a new runner state would silently render as a green pill.
     *
     * @covers ::normalizeStatus
     */
    public function test_an_unknown_status_is_not_coerced(): void
    {
        $this->assertSame('quarantined', Queue::normalizeStatus('quarantined'));
    }

    /**
     * @covers ::normalizeStatus
     */
    public function test_normalize_status_maps_every_synonym_the_ecosystem_knows(): void
    {
        $this->assertSame(Queue::STATUS_FAILURE, Queue::normalizeStatus('error'));
        $this->assertSame(Queue::STATUS_FAILURE, Queue::normalizeStatus('fatal'));
        $this->assertSame(Queue::STATUS_SUCCESS, Queue::normalizeStatus('completed'));
        $this->assertSame(Queue::STATUS_SUCCESS, Queue::normalizeStatus('done'));
        $this->assertSame(Queue::STATUS_SUCCESS, Queue::normalizeStatus('finished'));
        $this->assertSame(Queue::STATUS_SUCCESS, Queue::normalizeStatus('ok'));
        $this->assertSame(Queue::STATUS_WARNING, Queue::normalizeStatus('warn'));
        $this->assertSame(Queue::STATUS_RUNNING, Queue::normalizeStatus('processing'));
        $this->assertSame(Queue::STATUS_RUNNING, Queue::normalizeStatus('inprogress'));
        $this->assertSame(Queue::STATUS_RUNNING, Queue::normalizeStatus('in_progress'));
    }

    /**
     * @covers ::statusVariants
     */
    public function test_status_variants_cover_canonical_and_legacy_spellings(): void
    {
        $this->assertEqualsCanonicalizing(['failure', 'failed', 'error', 'fatal'], Queue::statusVariants(Queue::STATUS_FAILURE));
        $this->assertEqualsCanonicalizing(['failure', 'failed', 'error', 'fatal'], Queue::statusVariants('failed'));
        $this->assertEqualsCanonicalizing(['skipped', 'skpped'], Queue::statusVariants(Queue::STATUS_SKIPPED));
        $this->assertEqualsCanonicalizing(['success', 'completed', 'done', 'finished', 'ok'], Queue::statusVariants(Queue::STATUS_SUCCESS));
        $this->assertEqualsCanonicalizing(['warning', 'warn'], Queue::statusVariants(Queue::STATUS_WARNING));
    }

    /**
     * STATUS_ALIASES is a hand-copied duplicate of STATUS_ALIASES in the
     * orchestrator's orchestrator/status.py, and it had already drifted: the
     * Python side listed 12 synonyms, the PHP side 2. The consequence is silent
     * -- a job reported as 'error' was a failure to the orchestrator, the GUI
     * and the CLI, and neither a failure nor a warning to this API, so the
     * runner-facing controllers logged nothing at all for it.
     *
     * The Python file is not reachable from CI (a different repository) or from
     * inside the API container, so the expected table is pinned here and this
     * test is the thing that fails if either copy moves. When the orchestrator
     * checkout *is* on disk -- a developer box, the VM -- the file is parsed and
     * compared as well, which is what catches drift on the Python side.
     *
     * @covers ::normalizeStatus
     */
    public function test_the_alias_table_matches_the_orchestrator(): void
    {
        $expected = [
            'skpped' => Queue::STATUS_SKIPPED,
            'failed' => Queue::STATUS_FAILURE,
            'error' => Queue::STATUS_FAILURE,
            'fatal' => Queue::STATUS_FAILURE,
            'completed' => Queue::STATUS_SUCCESS,
            'done' => Queue::STATUS_SUCCESS,
            'finished' => Queue::STATUS_SUCCESS,
            'ok' => Queue::STATUS_SUCCESS,
            'warn' => Queue::STATUS_WARNING,
            'processing' => Queue::STATUS_RUNNING,
            'inprogress' => Queue::STATUS_RUNNING,
            'in_progress' => Queue::STATUS_RUNNING,
        ];

        $this->assertSame($expected, Queue::STATUS_ALIASES);

        $python = $this->orchestratorStatusAliases();

        if (null === $python) {
            $this->markTestIncomplete(
                'orchestrator/status.py is not on this machine; only the pinned table was checked. '
                .'Set PM_ORCHESTRATOR_STATUS_PY to compare against the Python source.'
            );
        }

        $this->assertSame(
            $expected,
            $python,
            'orchestrator/status.py and Queue::STATUS_ALIASES have diverged -- every reader would then '
            .'classify the same status_code differently'
        );
    }

    /**
     * @covers ::scopeWithJobOutcomeCounts
     */
    public function test_the_rollup_counts_each_outcome_separately(): void
    {
        $queue = $this->queueWithJobs([
            Queue::STATUS_SUCCESS,
            Queue::STATUS_SUCCESS,
            Queue::STATUS_WARNING,
            Queue::STATUS_FAILURE,
            Queue::STATUS_SKIPPED,
            Queue::STATUS_RUNNING,
        ]);

        $rolled = Queue::query()->withJobOutcomeCounts()->findOrFail($queue->id);

        $this->assertSame(6, $rolled->jobs_count);
        $this->assertSame(2, $rolled->jobs_succeeded_count);
        // The whole point of the new state: a warning is not a success.
        $this->assertSame(1, $rolled->jobs_warning_count);
        $this->assertSame(1, $rolled->jobs_failed_count);
        $this->assertSame(1, $rolled->jobs_skipped_count);
    }

    /**
     * @covers ::scopeWithJobOutcomeCounts
     */
    public function test_the_rollup_counts_rows_written_with_the_legacy_spellings(): void
    {
        $queue = $this->queueWithJobs(['failed', 'failure', 'skpped', 'skipped']);

        $rolled = Queue::query()->withJobOutcomeCounts()->findOrFail($queue->id);

        $this->assertSame(2, $rolled->jobs_failed_count);
        $this->assertSame(2, $rolled->jobs_skipped_count);
    }

    /**
     * The list endpoint serves the GUI, so the rollup has to be subselects on
     * the one query -- not an accessor firing five queries per row.
     *
     * @covers ::queryBuilder
     */
    public function test_the_list_query_stays_a_single_query(): void
    {
        $this->queueWithJobs([Queue::STATUS_SUCCESS, Queue::STATUS_WARNING]);
        $this->queueWithJobs([Queue::STATUS_FAILURE]);
        $this->queueWithJobs([Queue::STATUS_SKIPPED]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $queues = Queue::queryBuilder()->get();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame(1, $queries, 'the queues list must not fan out into a query per row');
        $this->assertNotNull($queues->first()->jobs_warning_count);
    }

    /**
     * @param string[] $statuses
     */
    private function queueWithJobs(array $statuses): Queue
    {
        $queue = Queue::factory()->create(['crawler_id' => $this->crawler->id]);

        foreach ($statuses as $status) {
            Job::factory()->create(['queue_id' => $queue->id, 'status_code' => $status]);
        }

        return $queue;
    }

    /**
     * STATUS_ALIASES parsed out of the orchestrator, or null when the file is
     * not on this machine.
     *
     * @return array<string, string>|null
     */
    private function orchestratorStatusAliases(): ?array
    {
        $candidates = array_filter([
            getenv('PM_ORCHESTRATOR_STATUS_PY') ?: null,
            base_path('../primemover-orchestrator/orchestrator/status.py'),
            '/opt/primemover/primemover-orchestrator/orchestrator/status.py',
        ]);

        foreach ($candidates as $candidate) {
            if ( ! is_readable($candidate)) {
                continue;
            }

            $source = (string) file_get_contents($candidate);

            if ( ! preg_match('/^STATUS_ALIASES[^=]*=\s*\{(.*?)^\}/ms', $source, $block)) {
                continue;
            }

            preg_match_all('/["\']([^"\']+)["\']\s*:\s*["\']([^"\']+)["\']/', $block[1], $pairs, PREG_SET_ORDER);

            $aliases = [];

            foreach ($pairs as $pair) {
                $aliases[$pair[1]] = $pair[2];
            }

            return $aliases;
        }

        return null;
    }
}
