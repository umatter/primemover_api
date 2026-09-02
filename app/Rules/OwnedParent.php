<?php

namespace App\Rules;

use App\Access\Isolation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Rejects attaching a row to a parent the caller cannot see.
 *
 * Without this, isolation covers reads only. Crawler is $guarded = [] and
 * UpdateCrawlerRequest requires experiment_id, so a researcher could PUT another
 * researcher's crawler with their own experiment_id and re-parent it -- and
 * because ownership is derived from the experiment, the crawler's whole subtree
 * of runs, jobs and reports would move with it. The same shape applies to
 * crawler_id on a run and queue_id on a job.
 *
 * A no-op while isolation is off or for service accounts, so it does not change
 * behaviour until the feature is switched on.
 */
class OwnedParent implements ValidationRule
{
    /** @param class-string<Model> $model The parent model the id refers to. */
    public function __construct(private string $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Isolation::bypasses()) {
            return;
        }

        if (null === $value || '' === $value) {
            return; // presence is a separate rule's job
        }

        $visible = $this->model::query()
            ->visibleTo()
            ->whereKey($value)
            ->exists();

        if ( ! $visible) {
            // Deliberately phrased as "not found", not "forbidden": telling a
            // caller that an id exists but belongs to someone else leaks the
            // existence of other researchers' rows.
            $fail('The selected :attribute could not be found.');
        }
    }
}
