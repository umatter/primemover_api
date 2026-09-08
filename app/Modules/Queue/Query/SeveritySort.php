<?php

namespace App\Modules\Queue\Query;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * ?sort=severity -- order rows by what their status_code MEANS.
 *
 * status_code is already an allowed sort on jobs and queues, and it is the
 * wrong sort: it orders the raw string, so 'error', 'failed', 'failure' and
 * 'fatal' -- four spellings of one outcome, all of them present in this
 * database -- scatter across the page alphabetically, with 'skipped' and
 * 'success' in between. Sorting by severity puts every failure together
 * whatever it is called.
 *
 * Applies to the model's own status_code column, so the same class serves
 * /jobs and /queues. Deliberately no tiebreaker: Spatie composes sorts, and
 * ?sort=severity,-id has to keep meaning what it says.
 */
class SeveritySort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        StatusQuery::applySeveritySort(
            $query,
            $descending,
            $query->getModel()->getTable().'.status_code'
        );
    }
}
