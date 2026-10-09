<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;

/**
 * Ownership checks for Configuration -- and, through ParamPolicy, its params,
 * which are the experiment's treatment.
 *
 * Unlike the other derived models, a configuration has no single chain to an
 * experiment: crawler -> configuration is not unique, so one configuration can
 * sit under crawlers in several researchers' experiments. Write access therefore
 * requires that NO crawler outside the caller's experiments uses it; otherwise a
 * change made "to my bot" would rewrite somebody else's treatment too.
 *
 * A configuration no crawler uses yet has nothing to derive from, so it falls
 * back to its own user_id. That is reliable here, unlike on crawlers and jobs:
 * a researcher creating one through the API is stamped as its owner.
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts, so these methods only run for isolated researchers.
 */
class ConfigurationPolicy
{
    public function view(User $user, Configuration $model): bool
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, Configuration $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, Configuration $model): bool
    {
        return $this->owns($user, $model);
    }

    protected function owns(User $user, Configuration $configuration): bool
    {
        $crawlers = Crawler::query()->where('configuration_id', $configuration->getKey());

        if ( ! $crawlers->exists()) {
            return (int) $configuration->user_id === (int) $user->getKey();
        }

        return ! $crawlers
            ->whereDoesntHave('experiment', fn ($q) => $q->where('experiments.user_id', $user->getKey()))
            ->exists();
    }
}
