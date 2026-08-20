<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;

/**
 * Ownership checks for Job.
 *
 * Through the QUEUE, not the crawler: jobs has no crawler_id, so
     * Job::crawler() is null and would deny every job.
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts, so these methods only run for isolated researchers.
 */
class JobPolicy
{
    public function view(User $user, Job $model): bool
    {
        return $this->ownedViaExperiment($user, $model->queue?->crawler?->experiment);
    }

    public function update(User $user, Job $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Job $model): bool
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
