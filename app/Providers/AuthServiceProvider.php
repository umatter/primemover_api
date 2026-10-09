<?php

namespace App\Providers;

use App\Access\Isolation;
use App\Models\User;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Param\Model\Param;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use App\Policies\ConfigurationPolicy;
use App\Policies\CrawlerPolicy;
use App\Policies\ExperimentPolicy;
use App\Policies\JobPolicy;
use App\Policies\ParamPolicy;
use App\Policies\QueuePolicy;
use App\Policies\ReportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        Experiment::class => ExperimentPolicy::class,
        Crawler::class => CrawlerPolicy::class,
        Queue::class => QueuePolicy::class,
        Job::class => JobPolicy::class,
        Report::class => ReportPolicy::class,
        Configuration::class => ConfigurationPolicy::class,
        Param::class => ParamPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Everything below is inert while isolation is off: bypasses() is then
        // true for every caller, so Gate::before allows each ability before any
        // policy runs and the API behaves exactly as it did before.
        //
        // Returning null (rather than false) when it does NOT bypass is
        // deliberate -- null means "no opinion", letting the policy decide.
        //
        // The ?User hint is load-bearing, not tidiness: Gate skips a before
        // callback it cannot call with the current user, so a non-nullable hint
        // makes this callback vanish for guests and the request falls through
        // to a policy that denies. Several existing tests construct controllers
        // directly with nobody authenticated, and every one of them would fail.
        Gate::before(fn (?User $user, string $ability) => Isolation::bypasses() ? true : null);

        // For the handful of platform-wide destructive routes. Only machine
        // callers should be able to disable every crawler or truncate data.
        Gate::define(
            'administer-platform',
            fn (?User $user) => Isolation::bypasses() || Isolation::isServiceAccount($user)
        );

        Passport::tokensExpireIn(now()->addDay());
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
