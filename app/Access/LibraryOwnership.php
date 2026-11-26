<?php

namespace App\Access;

use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use Illuminate\Database\Eloquent\Model;

/**
 * Who owns a Library item: an agent, a proxy or a configuration.
 *
 * None of them has a single chain to an experiment. Crawler -> item is not
 * unique, so one item can sit under crawlers in several researchers'
 * experiments, and what a crawler does with it is shared: a configuration's
 * params are the treatment, an agent is one Multilogin browser profile (cookies
 * and watch history included), a proxy is one exit IP. A researcher therefore
 * owns an item only when NO crawler outside their experiments uses it.
 *
 * An item no crawler uses yet has nothing to derive from, so it falls back to
 * its own user_id. That is reliable here, unlike on crawlers and jobs: a
 * researcher creating one through the API is stamped as its owner.
 *
 * Isolation is not checked here; callers (policies, rules) do that first.
 */
class LibraryOwnership
{
    /**
     * @param string $foreignKey the item's column on crawlers, e.g. 'agent_id'
     */
    public static function owns(User $user, Model $item, string $foreignKey): bool
    {
        $crawlers = Crawler::query()->where($foreignKey, $item->getKey());

        if ( ! $crawlers->exists()) {
            return (int) $item->user_id === (int) $user->getKey();
        }

        return ! $crawlers
            ->whereDoesntHave('experiment', fn ($q) => $q->where('experiments.user_id', $user->getKey()))
            ->exists();
    }
}
