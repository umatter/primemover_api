<?php

namespace App\Modules\Queue\Query;

use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * Asking the API "which of these need attention?" -- filtering and ordering by
 * the canonical status vocabulary rather than by the raw string in the column.
 *
 * Everything here exists because status_code is NOT a normalised column. The
 * runner never rewrites history and the API deliberately never coerces on write
 * (a 422 on the runner PUT would leave the row stuck at 'running'), so the same
 * outcome is spelled several ways in the same table: 256 job rows and 277 queue
 * rows say 'failed' where the current runner says 'failure', and 'skpped' is a
 * runner typo that persists in rows written before 2026-08-27. Consequently:
 *
 * - a filter that compares the raw string under-reports failures by more than
 *   half, which is why every value here is expanded through
 *   Queue::statusVariants() before it reaches the query;
 * - a SORT on the raw string is worse than useless, because it interleaves the
 *   vocabulary alphabetically -- 'failed' lands next to 'error' and two rows
 *   describing the identical outcome end up either side of 'skipped'. Hence the
 *   CASE expression in severityOrderExpression().
 *
 * The predicate a human actually wants is is_bad = failure OR warning
 * (docs/integration-contract.md 3.4), never failure alone: 'warning' is the
 * state the whole overhaul was introduced to surface, and a run that finished
 * degraded is exactly the one nobody would otherwise look at. filter[bad]=1
 * computes that server-side so no client has to carry the alias table.
 */
class StatusQuery
{
    /**
     * The name of the severity ordering, as both ?sort= and ?sortBy= spell it.
     */
    public const SEVERITY_SORT = 'severity';

    /**
     * is_bad. The one predicate that decides whether a human should look.
     *
     * @var string[]
     */
    public const BAD_STATUSES = [
        Queue::STATUS_FAILURE,
        Queue::STATUS_WARNING,
    ];

    /**
     * Truthy spellings accepted by filter[bad].
     *
     * Spatie converts the literal strings 'true'/'false' to booleans before the
     * filter is invoked, so those arrive already typed; everything else lands
     * here as a string off the query string.
     *
     * @var string[]
     */
    private const TRUTHY = ['1', 'true', 'yes', 'on', 'bad'];

    /**
     * The allowed filters every status-bearing list endpoint shares.
     *
     * Both are AllowedFilter::callback rather than ::exact, because an exact
     * filter compares the raw string and would answer "no failures" on a table
     * where half of them are spelled 'failed'.
     *
     * @return AllowedFilter[]
     */
    public static function filters(string $table): array
    {
        $column = self::qualify($table, 'status_code');

        return [
            // filter[status_code]=failure,warning -- a comma list, each value
            // expanded to its legacy spellings.
            AllowedFilter::callback('status_code', function (Builder $query, $value) use ($column): void {
                self::applyStatusFilter($query, self::variantsFor($value), $column);
            }),
            // filter[bad]=1 -- exactly status_code=failure,warning.
            AllowedFilter::callback('bad', function (Builder $query, $value) use ($column): void {
                if ( ! self::wantsBad($value)) {
                    return;
                }

                self::applyStatusFilter($query, self::badVariants(), $column);
            }),
        ];
    }

    /**
     * ?sort=severity / ?sort=-severity, for the Spatie sort pipeline.
     */
    public static function severitySort(): AllowedSort
    {
        return AllowedSort::custom(self::SEVERITY_SORT, new SeveritySort());
    }

    /**
     * Every raw spelling a requested status list should match.
     *
     * Accepts what Spatie hands a filter callback: a bare string, a comma list
     * it has already exploded into an array, or a nested array. An empty
     * request yields an empty list, which callers treat as "no filter" rather
     * than as "match nothing" -- filter[status_code]= is a client sending no
     * selection, not a client asking for the empty set.
     *
     * @return string[]
     */
    public static function variantsFor(mixed $value): array
    {
        $variants = [];

        foreach (self::flatten($value) as $requested) {
            foreach (explode(',', $requested) as $part) {
                $part = trim($part);

                if ('' === $part) {
                    continue;
                }

                // An unrecognised value expands to itself, so it matches
                // nothing and the endpoint answers with an empty page -- never
                // with everything, and never with a 400.
                $variants = array_merge($variants, Queue::statusVariants($part));
            }
        }

        return array_values(array_unique($variants));
    }

    /**
     * Every raw spelling of failure OR warning.
     *
     * @return string[]
     */
    public static function badVariants(): array
    {
        return self::variantsFor(self::BAD_STATUSES);
    }

    /**
     * Did filter[bad] actually ask for the bad ones?
     *
     * filter[bad]=0 means "no, show me everything", which is a no-op and not an
     * inversion: there is no filter[bad]=0 meaning "only the good ones", and
     * inventing one would make 'unknown' silently good.
     */
    public static function wantsBad(mixed $value): bool
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_bool($value)) {
            return $value;
        }

        if (null === $value) {
            return false;
        }

        return in_array(mb_strtolower(trim((string) $value)), self::TRUTHY, true);
    }

    public static function applyStatusFilter(Builder $query, array $variants, string $column): void
    {
        if ([] === $variants) {
            return;
        }

        // A plain whereIn on the raw column, so the index on
        // queues.status_code is usable. The variants are the normalisation.
        $query->whereIn($column, $variants);
    }

    /**
     * The severity CASE expression and its bindings, worst last.
     *
     * Portable on purpose: MySQL's FIELD() does not exist in sqlite, which is
     * what the test suite runs on, so a divergence there would only show up in
     * production. CASE ... WHEN ... IN is understood identically by both.
     *
     * The ranks are Queue::SEVERITY_SORT_RANK -- deliberately NOT the
     * child-summarising SEVERITY table, because this one has an 'unknown' rank
     * and that one must not gain one. See the constant's docblock.
     *
     * lower(trim(...)) is applied to the column rather than relying on the
     * collation: this expression has to classify EVERY row, including the ones
     * with a NULL or empty status_code, and a NULL simply fails every IN test
     * and falls through to the ELSE -- which is the unknown rank, exactly as
     * the contract requires. (The filters above deliberately do not normalise
     * in SQL, because they can use an index and this cannot.)
     *
     * @return array{0: string, 1: string[]}
     */
    public static function severityOrderExpression(string $column): array
    {
        self::assertSafeColumn($column);

        $sql = 'case';
        $bindings = [];

        foreach (Queue::SEVERITY_SORT_RANK as $status => $rank) {
            if (Queue::STATUS_UNKNOWN === $status) {
                // Not a status the runner writes -- it is the ELSE.
                continue;
            }

            $variants = Queue::statusVariants($status);
            $placeholders = implode(', ', array_fill(0, count($variants), '?'));

            $sql .= ' when lower(trim('.$column.')) in ('.$placeholders.') then '.(int) $rank;
            $bindings = array_merge($bindings, $variants);
        }

        $sql .= ' else '.Queue::UNKNOWN_SEVERITY_SORT_RANK.' end';

        return [$sql, $bindings];
    }

    public static function applySeveritySort(Builder $query, bool $descending, string $column): void
    {
        [$sql, $bindings] = self::severityOrderExpression($column);

        $query->orderByRaw($sql.' '.($descending ? 'desc' : 'asc'), $bindings);
    }

    /**
     * Is this ?sortBy= value asking for the severity ordering?
     */
    public static function isSeveritySort(mixed $sortBy): bool
    {
        if ( ! is_string($sortBy)) {
            return false;
        }

        return self::SEVERITY_SORT === ltrim(mb_strtolower(trim($sortBy)), '-');
    }

    /**
     * Apply ?sortBy=severity / ?sortBy=-severity as the PRIMARY ordering.
     *
     * reorder() first, because Spatie has already applied defaultSort('-id') by
     * the time a repository gets the builder; appending to it -- which is what
     * the plain ?sortBy= path does -- would leave "order by id desc, severity"
     * and quietly sort by nothing of the sort. Severity is a worst-first
     * ordering or it is not an ordering at all.
     *
     * The id tiebreaker keeps the page stable inside a rank, which is what
     * makes pagination reproducible.
     */
    public static function applySeveritySortBy(Builder $query, mixed $sortBy, string $table): void
    {
        $query->reorder();

        self::applySeveritySort($query, self::isDescending($sortBy), self::qualify($table, 'status_code'));

        $query->orderByDesc(self::qualify($table, 'id'));
    }

    public static function isDescending(mixed $sortBy): bool
    {
        return is_string($sortBy) && str_starts_with(trim($sortBy), '-');
    }

    /**
     * @return string[]
     */
    private static function flatten(mixed $value): array
    {
        if (is_array($value)) {
            $flat = [];

            foreach ($value as $item) {
                $flat = array_merge($flat, self::flatten($item));
            }

            return $flat;
        }

        if (null === $value || is_bool($value)) {
            // filter[status_code]=true is nonsense; Spatie has already turned
            // the literal into a boolean, and there is no status it names.
            return [];
        }

        return [(string) $value];
    }

    private static function qualify(string $table, string $column): string
    {
        $qualified = $table.'.'.$column;

        self::assertSafeColumn($qualified);

        return $qualified;
    }

    /**
     * Identifiers reach severityOrderExpression() as raw SQL, so they may only
     * ever come from a model's own table name -- never from a request. This is
     * a tripwire for the day someone wires one through.
     */
    private static function assertSafeColumn(string $column): void
    {
        if (1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
            throw new InvalidArgumentException('unsafe column identifier: '.$column);
        }
    }
}
