<?php

namespace App\Policies;

use App\Access\LibraryOwnership;
use App\Models\User;
use App\Modules\Agent\Model\Agent;

/**
 * Who may change or delete a agent. Every bot using it shares it, so the rule is
 * LibraryOwnership's: no crawler outside the caller's experiments uses it.
 * Reading is not restricted here (the index is scoped instead, ScopesToLibraryOwner).
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts (the runner writes agents through runner/agents/{id}).
 */
class AgentPolicy
{
    public function update(User $user, Agent $model): bool
    {
        return LibraryOwnership::owns($user, $model, 'agent_id');
    }

    public function delete(User $user, Agent $model): bool
    {
        return LibraryOwnership::owns($user, $model, 'agent_id');
    }
}
