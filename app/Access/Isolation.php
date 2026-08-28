<?php

namespace App\Access;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * The single place that decides whether per-user isolation applies to a request.
 *
 * Everything else -- scopes, policies, validation rules, middleware -- asks this
 * class rather than reading config itself, so there is exactly one definition of
 * "does this caller see everything".
 *
 * Two deliberate design decisions:
 *
 * 1. The caller is resolved LAZILY, at call time, via Auth::user(). There is no
 *    middleware populating a request-scoped context. This app's Http\Kernel
 *    defines no $middlewarePriority, so a context middleware could be ordered
 *    after SubstituteBindings, and anything that made binding fail would 404 the
 *    runner's status PUTs -- a silent, total outage of job execution.
 *
 * 2. It fails OPEN when there is no authenticated user. Every route this guards
 *    already sits behind auth:api, so an unauthenticated caller never reaches a
 *    scoped query; failing closed here would only create an outage path without
 *    adding protection.
 */
class Isolation
{
    /** Whether isolation is switched on at all. */
    public static function enabled(): bool
    {
        return (bool) config('primemover.isolation.enabled', false);
    }

    /** The authenticated user's id, or null when there is none. */
    public static function userId(): ?int
    {
        $id = Auth::id();

        return null === $id ? null : (int) $id;
    }

    /**
     * Whether a user is a machine caller that sees everything.
     *
     * The runner, the orchestrator CLI, the MCP server and the e2e suite all
     * authenticate as the same account today, so this is what keeps job
     * execution working while researchers are isolated from each other.
     */
    public static function isServiceAccount(?User $user = null): bool
    {
        $id = $user?->getKey() ?? self::userId();

        if (null === $id) {
            return false;
        }

        return in_array((int) $id, self::serviceAccountIds(), true);
    }

    /** @return array<int, int> */
    public static function serviceAccountIds(): array
    {
        return (array) config('primemover.isolation.service_account_ids', []);
    }

    /**
     * Whether this request bypasses isolation entirely.
     *
     * True when the feature is off, when there is no authenticated user, or when
     * the caller is a service account. Callers treat this as "do nothing".
     */
    public static function bypasses(): bool
    {
        if ( ! self::enabled()) {
            return true;
        }

        if (null === self::userId()) {
            return true;
        }

        return self::isServiceAccount();
    }
}
