<?php

namespace Tests\Feature\Modules\Queue\Query;

use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Query\StatusQuery;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * The pieces behind filter[status_code], filter[bad] and sort=severity.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Query\StatusQuery
 */
class StatusQueryTest extends TestCase
{
    /**
     * A requested status has to match every spelling of itself that the
     * database actually holds, or the filter under-reports: 256 job rows and
     * 277 queue rows say 'failed' where the current runner says 'failure'.
     *
     * @covers ::variantsFor
     */
    public function test_a_requested_status_expands_to_its_legacy_spellings(): void
    {
        $this->assertEqualsCanonicalizing(
            ['failure', 'failed', 'error', 'fatal'],
            StatusQuery::variantsFor('failure')
        );

        // ...and asking by the legacy spelling means the same thing.
        $this->assertEqualsCanonicalizing(
            ['failure', 'failed', 'error', 'fatal'],
            StatusQuery::variantsFor('failed')
        );

        $this->assertEqualsCanonicalizing(['skipped', 'skpped'], StatusQuery::variantsFor('SKIPPED'));
    }

    /**
     * A comma list arrives from Spatie already exploded, but a client can also
     * send filter[status_code][]=a&filter[status_code][]=b, and a single value
     * arrives as a bare string. All three have to mean the same thing.
     *
     * @covers ::variantsFor
     */
    public function test_a_comma_list_a_php_array_and_a_bare_string_are_the_same_request(): void
    {
        $expected = ['failure', 'failed', 'error', 'fatal', 'warning', 'warn'];

        $this->assertEqualsCanonicalizing($expected, StatusQuery::variantsFor('failure,warning'));
        $this->assertEqualsCanonicalizing($expected, StatusQuery::variantsFor(['failure', 'warning']));
        $this->assertEqualsCanonicalizing($expected, StatusQuery::variantsFor([' failure ', 'warn']));
    }

    /**
     * Nothing requested is not the same as "match nothing": an empty filter
     * value is a client that sent no selection, and it must leave the page
     * alone rather than emptying it.
     *
     * @covers ::variantsFor
     */
    public function test_an_empty_request_selects_nothing_to_filter_on(): void
    {
        $this->assertSame([], StatusQuery::variantsFor(''));
        $this->assertSame([], StatusQuery::variantsFor(null));
        $this->assertSame([], StatusQuery::variantsFor([]));
        $this->assertSame([], StatusQuery::variantsFor(',,  ,'));
        // Spatie converts the literal 'true'/'false' before the filter sees it;
        // neither names a status.
        $this->assertSame([], StatusQuery::variantsFor(true));
    }

    /**
     * An unrecognised value expands to itself, so it matches no row -- an empty
     * page, never every page, and never a 400.
     *
     * @covers ::variantsFor
     */
    public function test_an_unknown_status_expands_to_itself_and_nothing_else(): void
    {
        $this->assertSame(['quarantined'], StatusQuery::variantsFor('quarantined'));
    }

    /**
     * filter[bad] is the contract's is_bad: failure OR warning, never failure
     * alone. Failure alone is exactly the mistake that kept degraded runs
     * invisible, which is why this is computed here and not by each client.
     *
     * @covers ::badVariants
     */
    public function test_bad_is_failure_or_warning_in_every_spelling(): void
    {
        $this->assertEqualsCanonicalizing(
            ['failure', 'failed', 'error', 'fatal', 'warning', 'warn'],
            StatusQuery::badVariants()
        );

        // Nothing else creeps in -- a 'skipped' or a NULL run is not something
        // a human has to look at.
        $this->assertNotContains('skipped', StatusQuery::badVariants());
        $this->assertNotContains('success', StatusQuery::badVariants());
        $this->assertNotContains('running', StatusQuery::badVariants());
    }

    /**
     * @covers ::wantsBad
     */
    public function test_bad_is_only_applied_when_it_was_actually_asked_for(): void
    {
        $this->assertTrue(StatusQuery::wantsBad('1'));
        $this->assertTrue(StatusQuery::wantsBad(true));
        $this->assertTrue(StatusQuery::wantsBad('yes'));

        // filter[bad]=0 means "show me everything", not "show me the good
        // ones": there is no inverse predicate, and inventing one would make
        // every unknown row silently good.
        $this->assertFalse(StatusQuery::wantsBad('0'));
        $this->assertFalse(StatusQuery::wantsBad(false));
        $this->assertFalse(StatusQuery::wantsBad(''));
        $this->assertFalse(StatusQuery::wantsBad(null));
    }

    /**
     * The SQL severity ranking and the PHP one must agree on every value, or a
     * sorted page and a rendered pill disagree about the same row.
     *
     * Executed as real SQL rather than string-matched, because the point of the
     * CASE expression is that sqlite (here) and MySQL (production) both
     * understand it -- MySQL's FIELD() would have passed a string comparison
     * and failed in the test suite.
     *
     * @covers ::severityOrderExpression
     */
    public function test_the_sql_ranking_agrees_with_the_php_ranking_for_every_spelling(): void
    {
        $spellings = array_merge(
            array_keys(Queue::SEVERITY_SORT_RANK),
            array_keys(Queue::STATUS_ALIASES),
            ['FAILURE', '  warning  ', 'quarantined', '', null]
        );

        [$sql, $bindings] = StatusQuery::severityOrderExpression('queues.status_code');

        foreach ($spellings as $spelling) {
            if (Queue::STATUS_UNKNOWN === $spelling) {
                // Not a code the runner writes; it is the name of the ELSE.
                continue;
            }

            $queue = Queue::factory()->create([
                'crawler_id' => $this->crawler->id,
                'status_code' => $spelling,
            ]);

            $rank = (int) DB::table('queues')
                ->selectRaw($sql.' as severity_rank', $bindings)
                ->where('id', $queue->id)
                ->value('severity_rank');

            $this->assertSame(
                Queue::severityRank($spelling),
                $rank,
                'SQL and PHP disagree about the severity of '.var_export($spelling, true)
            );
        }
    }

    /**
     * The rule that is easiest to break by accident: rank unknown at 0 and
     * every unreported or misspelled row hides at the clean end of the list,
     * which is where nobody looks.
     *
     * @covers ::severityOrderExpression
     */
    public function test_unknown_ranks_above_running_and_below_warning_never_as_success(): void
    {
        $this->assertSame(3, Queue::severityRank(null));
        $this->assertSame(3, Queue::severityRank(''));
        $this->assertSame(3, Queue::severityRank('quarantined'));

        $this->assertGreaterThan(Queue::severityRank(Queue::STATUS_RUNNING), Queue::severityRank(null));
        $this->assertLessThan(Queue::severityRank(Queue::STATUS_WARNING), Queue::severityRank(null));
        $this->assertNotSame(Queue::severityRank(Queue::STATUS_SUCCESS), Queue::severityRank(null));
    }

    /**
     * The four spellings of failure rank identically -- which is the whole
     * reason severity exists as a sort. Ordering the raw string puts 'error'
     * and 'failed' either side of 'skipped'.
     *
     * @covers \App\Modules\Queue\Model\Queue::severityRank
     */
    public function test_every_spelling_of_one_outcome_shares_one_rank(): void
    {
        $this->assertSame(5, Queue::severityRank('failure'));
        $this->assertSame(5, Queue::severityRank('failed'));
        $this->assertSame(5, Queue::severityRank('error'));
        $this->assertSame(5, Queue::severityRank('fatal'));

        $this->assertSame(1, Queue::severityRank('skipped'));
        $this->assertSame(1, Queue::severityRank('skpped'));

        $this->assertSame(4, Queue::severityRank('warning'));
        $this->assertSame(4, Queue::severityRank('warn'));
    }

    /**
     * The ranks are the contract's, not the child-summarising SEVERITY table's.
     * The difference is the 'unknown' entry, and it matters: SEVERITY has none
     * and must not gain one.
     *
     * @covers \App\Modules\Queue\Model\Queue::severityRank
     */
    public function test_the_rank_table_is_the_one_the_contract_specifies(): void
    {
        $this->assertSame([
            'success' => 0,
            'skipped' => 1,
            'running' => 2,
            'unknown' => 3,
            'warning' => 4,
            'failure' => 5,
        ], Queue::SEVERITY_SORT_RANK);

        $this->assertSame(3, Queue::UNKNOWN_SEVERITY_SORT_RANK);
    }

    /**
     * The column reaches the CASE expression as raw SQL, so it may only ever
     * come from a model's own table name. This is the tripwire for the day
     * someone wires a request parameter through.
     *
     * @covers ::severityOrderExpression
     */
    public function test_a_column_identifier_that_is_not_an_identifier_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StatusQuery::severityOrderExpression('status_code) --');
    }

    /**
     * @covers ::isSeveritySort
     * @covers ::isDescending
     */
    public function test_the_sort_by_parameter_is_recognised_in_both_directions(): void
    {
        $this->assertTrue(StatusQuery::isSeveritySort('severity'));
        $this->assertTrue(StatusQuery::isSeveritySort('-severity'));
        $this->assertTrue(StatusQuery::isSeveritySort(' SEVERITY '));
        $this->assertFalse(StatusQuery::isSeveritySort('status_code'));
        $this->assertFalse(StatusQuery::isSeveritySort(null));

        $this->assertTrue(StatusQuery::isDescending('-severity'));
        $this->assertFalse(StatusQuery::isDescending('severity'));
    }
}
