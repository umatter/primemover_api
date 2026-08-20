<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Experiment\Model\Experiment;

/**
 * Ownership checks for Experiment.
 *
 * The experiment is the ownership root: compare directly.
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts, so these methods only run for isolated researchers.
 */
class ExperimentPolicy
{
    public function view(User $user, Experiment $model): bool
    {
        return (int) $model->user_id === (int) $user->getKey();
    }

    public function update(User $user, Experiment $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Experiment $model): bool
    {
        return $this->view($user, $model);
    }

}
