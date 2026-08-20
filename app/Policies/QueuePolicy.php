<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Queue\Model\Queue;

/**
 * Ownership checks for Queue.
 *
 * A run is owned through its crawler's experiment.
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts, so these methods only run for isolated researchers.
 */
class QueuePolicy
{
    public function view(User $user, Queue $model): bool
    {
        return $this->ownedViaExperiment($user, $model->crawler?->experiment);
    }

    public function update(User $user, Queue $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Queue $model): bool
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
