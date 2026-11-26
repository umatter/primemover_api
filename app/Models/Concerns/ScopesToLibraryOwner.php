<?php

namespace App\Models\Concerns;

use App\Access\Isolation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts a Library list (agents, proxies, configurations) to the items the
 * current caller owns -- LibraryOwnership::owns() as a query: no crawler outside
 * the caller's experiments uses the item, and either one of theirs does or,
 * unused, its user_id is theirs. LibraryListScopeTest pins that the two agree.
 *
 * The model must have a `crawlers()` has-many. Applied explicitly to index
 * queries only: reading one item by id stays unscoped, because a bot's current
 * item (possibly a legacy shared one) must still load in Edit setup.
 */
trait ScopesToLibraryOwner
{
    /**
     * A no-op when isolation is off, when there is no authenticated user, or
     * when the caller is a service account.
     */
    public function scopeOwnedByCaller(Builder $query): Builder
    {
        if (Isolation::bypasses()) {
            return $query;
        }

        $userId = Isolation::userId();
        $table = $this->getTable();

        return $query
            ->whereDoesntHave('crawlers', fn (Builder $crawlers) => $crawlers
                ->whereDoesntHave('experiment', fn (Builder $e) => $e->where('experiments.user_id', $userId)))
            ->where(fn (Builder $q) => $q->whereHas('crawlers')->orWhere("{$table}.user_id", $userId));
    }
}
