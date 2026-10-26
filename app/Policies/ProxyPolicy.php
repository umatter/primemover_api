<?php

namespace App\Policies;

use App\Access\LibraryOwnership;
use App\Models\User;
use App\Modules\Proxy\Model\Proxy;

/**
 * Who may change or delete a proxy. Every bot using it shares it, so the rule is
 * LibraryOwnership's: no crawler outside the caller's experiments uses it.
 * Reading is not restricted here (the index is scoped instead, ScopesToLibraryOwner).
 *
 * Gate::before short-circuits every ability while isolation is off or for
 * service accounts (the runner writes agents through runner/agents/{id}).
 */
class ProxyPolicy
{
    public function update(User $user, Proxy $model): bool
    {
        return LibraryOwnership::owns($user, $model, 'proxy_id');
    }

    public function delete(User $user, Proxy $model): bool
    {
        return LibraryOwnership::owns($user, $model, 'proxy_id');
    }
}
