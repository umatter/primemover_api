<?php

namespace Tests\Feature\Modules\Job\Http\Controller\Api\v1;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Tests\TestCase;

/**
 * filter[status_code], filter[bad] and sort=severity on GET /jobs.
 *
 * The same three additions as on /queues, and for the same reason -- a
 * researcher asking which jobs need attention got HTTP 400, because
 * status_code was an allowed sort and not an allowed filter and this repo
 * leaves Spatie's invalid-filter exception on.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Http\Controller\Api\v1\JobController
 */
class JobStatusFilterTest extends TestCase
{
    private Queue $run;

    protected function setUp(): void
    {
        parent::setUp();

        // A run of this test's own: the base fixture seeds ten jobs with
        // randomised status codes onto $this->queue.
        $this->run = Queue::factory()->create(['crawler_id' => $this->crawler->id]);
    }

    /**
     * 256 job rows spell it 'failed' and the current runner spells it
     * 'failure'. WHERE status_code = 'failure' under-reports historical
     * failures by more than half.
     *
     * @covers ::index
     */
    public function test_filtering_by_failure_returns_the_rows_stored_as_failed(): void
    {
        $legacy = $this->job('failed');
        $current = $this->job(Queue::STATUS_FAILURE);
        $this->job(Queue::STATUS_SUCCESS);

        $this->assertSame([$current->id, $legacy->id], $this->idsFrom('filter[status_code]=failure'));
    }

    /**
     * @covers ::index
     */
    public function test_the_legacy_skipped_typo_is_matched_too(): void
    {
        // 'skpped' is the runner's own typo (JoblistState SKIPPED_*), and rows
        // written before 2026-08-27 persist with it.
        $typo = $this->job('skpped');
        $correct = $this->job(Queue::STATUS_SKIPPED);
        $this->job(Queue::STATUS_SUCCESS);

        $this->assertEqualsCanonicalizing(
            [$typo->id, $correct->id],
            $this->idsFrom('filter[status_code]=skipped')
        );
    }

    /**
     * The predicate the contract calls is_bad. Never failure alone: 'warning'
     * is the state the whole overhaul was built to surface, and a job that
     * finished having silently lost its treatment is exactly the one that no
     * other query would show.
     *
     * @covers ::index
     */
    public function test_bad_returns_exactly_failure_and_warning(): void
    {
        $expected = [
            $this->job(Queue::STATUS_FAILURE)->id,
            $this->job('error')->id,
            $this->job(Queue::STATUS_WARNING)->id,
            $this->job('warn')->id,
        ];

        $this->job(Queue::STATUS_SUCCESS);
        $this->job(Queue::STATUS_SKIPPED);
        $this->job(Queue::STATUS_RUNNING);
        $this->job(null);

        $this->assertEqualsCanonicalizing($expected, $this->idsFrom('filter[bad]=1'));
    }

    /**
     * @covers ::index
     */
    public function test_a_comma_list_selects_several_statuses(): void
    {
        $skipped = $this->job('skpped');
        $failed = $this->job('failed');
        $this->job(Queue::STATUS_SUCCESS);

        $this->assertEqualsCanonicalizing(
            [$skipped->id, $failed->id],
            $this->idsFrom('filter[status_code]=skipped,failure')
        );
    }

    /**
     * @covers ::index
     */
    public function test_an_unrecognised_status_value_returns_an_empty_page(): void
    {
        $this->job(Queue::STATUS_FAILURE);
        $this->job(Queue::STATUS_SUCCESS);

        $this->assertSame([], $this->idsFrom('filter[status_code]=not-a-status'));
    }

    /**
     * @covers ::index
     */
    public function test_an_empty_status_value_leaves_the_page_alone(): void
    {
        $this->job(Queue::STATUS_FAILURE);
        $this->job(Queue::STATUS_SUCCESS);

        $this->assertCount(2, $this->idsFrom('filter[status_code]='));
    }

    /**
     * @covers ::index
     */
    public function test_an_unknown_filter_key_is_still_rejected_outright(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->user)
            ->getJson('/api/v1/jobs?filter[nonsense]=1')
            ->assertStatus(400);
    }

    /**
     * The four spellings of failure have to land together; ordering the raw
     * string scatters them alphabetically around 'skipped' and 'success'.
     *
     * @covers ::index
     */
    public function test_severity_sort_orders_by_meaning_not_by_spelling(): void
    {
        $this->job('failed');
        $this->job(Queue::STATUS_SUCCESS);
        $this->job('fatal');
        $this->job(Queue::STATUS_SKIPPED);
        $this->job(Queue::STATUS_FAILURE);
        $this->job(Queue::STATUS_WARNING);

        $statuses = $this->statusesFrom('sortBy=severity');

        $this->assertSame(
            ['success', 'skipped', 'warning', 'failure', 'fatal', 'failed'],
            $statuses
        );

        // Stated as the property, not as a sequence: every spelling of failure
        // is one contiguous block.
        $this->assertSame([3, 4, 5], array_keys(array_filter(
            $statuses,
            fn (?string $status) => 5 === Queue::severityRank($status)
        )));
    }

    /**
     * The jobs table holds thousands of NULL status_codes. They rank as
     * unknown, between running and warning -- never as success, where nobody
     * would ever scroll to find them.
     *
     * @covers ::index
     */
    public function test_a_job_with_no_status_ranks_as_unknown_not_as_success(): void
    {
        $this->job(Queue::STATUS_SUCCESS);
        $this->job(Queue::STATUS_RUNNING);
        $this->job(null);
        $this->job(Queue::STATUS_WARNING);

        $this->assertSame(
            ['success', 'running', null, 'warning'],
            $this->statusesFrom('sortBy=severity')
        );
    }

    /**
     * @covers ::index
     */
    public function test_severity_sort_descending_puts_the_worst_first(): void
    {
        $this->job(Queue::STATUS_SUCCESS);
        $this->job(Queue::STATUS_WARNING);
        $this->job('failed');

        $this->assertSame(
            ['failed', 'warning', 'success'],
            $this->statusesFrom('sortBy=-severity')
        );
    }

    /**
     * Severity must replace the default -id ordering rather than be appended to
     * it, or it is a sort that answers 200 and does nothing.
     *
     * @covers ::index
     */
    public function test_severity_sort_replaces_the_default_id_ordering(): void
    {
        $oldestFailure = $this->job(Queue::STATUS_FAILURE);
        $newestSuccess = $this->job(Queue::STATUS_SUCCESS);

        $this->assertSame([$oldestFailure->id, $newestSuccess->id], $this->idsFrom('sortBy=-severity'));
    }

    /**
     * @covers ::index
     */
    public function test_the_spatie_sort_parameter_offers_the_same_ordering(): void
    {
        $this->job('failed');
        $this->job(Queue::STATUS_SUCCESS);
        $this->job(Queue::STATUS_WARNING);

        $this->assertSame(
            ['success', 'warning', 'failed'],
            $this->statusesFrom('sort=severity,-id')
        );
    }

    /**
     * @covers ::index
     */
    public function test_the_filter_and_the_sort_compose(): void
    {
        $this->job(Queue::STATUS_SUCCESS);
        $this->job(Queue::STATUS_WARNING);
        $this->job('fatal');

        $this->assertSame(['fatal', 'warning'], $this->statusesFrom('filter[bad]=1&sortBy=-severity'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function page(string $query): array
    {
        return $this->actingAs($this->user)
            ->getJson('/api/v1/jobs?queue='.$this->run->id.'&'.$query)
            ->assertOk()
            ->json('data');
    }

    /**
     * @return int[]
     */
    private function idsFrom(string $query): array
    {
        return array_map(fn (array $row) => $row['id'], $this->page($query));
    }

    /**
     * @return array<int, null|string>
     */
    private function statusesFrom(string $query): array
    {
        return array_map(fn (array $row) => $row['status_code'] ?? null, $this->page($query));
    }

    private function job(?string $status): Job
    {
        return Job::factory()->create([
            'queue_id' => $this->run->id,
            'status_code' => $status,
        ]);
    }
}
