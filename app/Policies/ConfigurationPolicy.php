<?php

namespace App\Policies;

use App\Access\LibraryOwnership;
use App\Models\User;
use App\Modules\Configuration\Model\Configuration;

/**
 * Ownership checks for Configuration -- and, through ParamPolicy, its params,
 * which are the experiment's treatment.
 *
 * A configuration has no single chain to an experiment; LibraryOwnership holds
 * the rule (no crawler outside the caller's experiments uses it), shared with
 * AttachableToCrawler so attaching and writing agree.
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
        return LibraryOwnership::owns($user, $configuration, 'configuration_id');
    }
}
