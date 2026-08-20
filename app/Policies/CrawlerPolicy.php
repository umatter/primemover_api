<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Crawler\Model\Crawler;

/**
 * Ownership checks for Crawler.
 *
 * A crawler is owned by whoever owns its experiment.
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts, so these methods only run for isolated researchers.
 */
class CrawlerPolicy
{
    public function view(User $user, Crawler $model): bool
    {
        return $this->ownedViaExperiment($user, $model->experiment);
    }

    public function update(User $user, Crawler $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Crawler $model): bool
    {
        return $this->view($user, $model);
    }

    /** Shared tail of every derived chain. Missing ancestor means not owned. */
    protected function ownedViaExperiment(User $user, ?Experiment $experiment): bool
    {
        if ($experiment === null) {
            return false;
        }

        return (int) $experiment->user_id === (int) $user->getKey();
    }
}
