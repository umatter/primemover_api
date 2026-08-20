<?php

namespace App\Policies;

use App\Access\Isolation;
use App\Models\User;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Report\Model\Report;

/**
 * Ownership checks for Report.
 *
 * Reports hang off a job, so the chain is the job's.
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts, so these methods only run for isolated researchers.
 */
class ReportPolicy
{
    public function view(User $user, Report $model): bool
    {
        return $this->ownedViaExperiment($user, $model->job?->queue?->crawler?->experiment);
    }

    public function update(User $user, Report $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Report $model): bool
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
