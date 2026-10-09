<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Param\Model\Param;

/**
 * A param is owned by whoever owns its configuration. Missing configuration
 * means not owned.
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts, so these methods only run for isolated researchers.
 */
class ParamPolicy
{
    public function __construct(private ConfigurationPolicy $configurations) {}

    public function view(User $user, Param $model): bool
    {
        return null !== $model->configuration && $this->configurations->view($user, $model->configuration);
    }

    public function update(User $user, Param $model): bool
    {
        return null !== $model->configuration && $this->configurations->update($user, $model->configuration);
    }

    public function delete(User $user, Param $model): bool
    {
        return null !== $model->configuration && $this->configurations->delete($user, $model->configuration);
    }
}
