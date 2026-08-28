<?php

namespace App\Models\Concerns;

use App\Access\Isolation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts a query to rows the current caller owns.
 *
 * Ownership is DERIVED from the experiment the row hangs off, not read from the
 * row's own user_id column. The relation path to walk is declared per model in
 * OWNERSHIP_PATH; an empty path means the model is the experiment itself.
 *
 * Why derived: experiments are split across several users, but every crawler and
 * job in this database is stamped user 1 (the account the runner and the upload
 * pipeline authenticate as). Reading user_id per row would therefore show a
 * researcher an experiment containing no bots, and no backfill could fix it
 * while the ingest path keeps stamping the uploader.
 *
 * This is applied explicitly in repositories. It is deliberately NOT a global
 * scope -- see the warning in config/primemover.php.
 */
trait ScopesToOwner
{
    /**
     * Limit the query to what the current caller may see.
     *
     * A no-op when isolation is off, when there is no authenticated user, or
     * when the caller is a service account.
     */
    public function scopeVisibleTo(Builder $query): Builder
    {
        if (Isolation::bypasses()) {
            return $query;
        }

        $userId = Isolation::userId();
        $path = static::OWNERSHIP_PATH;

        // The experiment itself: compare directly, no relation to walk.
        if ('' === $path) {
            return $query->where($this->getTable().'.user_id', $userId);
        }

        return $query->whereHas($path, function (Builder $q) use ($userId): void {
            $q->where('experiments.user_id', $userId);
        });
    }
}
