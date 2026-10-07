<?php

return [
    'throttle' => [
        'whitelist' => env('THROTTLE_WHITELIST', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-user isolation
    |--------------------------------------------------------------------------
    |
    | The experiment is the unit of ownership. Crawlers, queues, jobs and
    | reports inherit visibility from the experiment they hang off, through
    | their foreign keys -- their own user_id column is NOT consulted. That is
    | deliberate: experiments are split across users, but every crawler and job
    | in this database belongs to user 1, so per-row ownership would show a
    | researcher an experiment containing no bots.
    |
    | Disabled by default. With 'enabled' false every scope and policy
    | short-circuits and responses are byte-identical to the unscoped API, so
    | this code is inert until it is switched on deliberately.
    |
    | WARNING -- do not scope outlets or terms, under any flag. The Java
    | runner's OutletsProvider is a Spring initMethod bean whose refresh() has
    | no catch, so an error from GET /outlets stops the runner BOOTING, not
    | merely stops it seeing work.
    |
    | WARNING -- do not implement this with Eloquent global scopes.
    | SubstituteBindings applies them to route-model binding, which would 404
    | the runner's three status PUTs (runner/agents/{agent}, runner/jobs/{job},
    | runner/queues/{queue}) for any row it does not own, leaving queues stuck
    | in processing forever, silently.
    |
    */
    'isolation' => [
        'enabled' => env('PM_ISOLATION_ENABLED', false),

        /*
         | Users who see everything. Machine callers, not people: every runner
         | account (ex63 = admin@primemover.example, user 1; the runner-only VM =
         | runner@primemover.example, user 3), which the orchestrator CLI/MCP/e2e also use.
         | They are also the only valid targets for experiments.runner_user_id;
         | the two work-fetching queries partition them by that column.
         */
        'service_account_ids' => array_values(array_filter(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', (string) env('PM_SERVICE_ACCOUNT_IDS', '1'))), 'strlen')
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Runner assignment
    |--------------------------------------------------------------------------
    |
    | experiments.runner_user_id names the runner account that executes an
    | experiment. NULL means this default. Runner accounts are the
    | service_account_ids above -- they see everything (status reporting needs
    | that) but the two work-fetching queries only hand them experiments
    | assigned to them.
    |
    | WARNING -- default_user_id MUST be one of service_account_ids. With
    | isolation on, the default runner only sees the experiments partitioned
    | to it; if it isn't in the list, it sees nothing and the platform stops
    | with an empty work list, silently.
    |
    | WARNING -- removing an id from service_account_ids strands every
    | experiment already assigned to it: no runner fetches that id's work
    | any more, and re-saving the experiment 422s because the id is no
    | longer a valid runner_user_id target. Reassign those experiments
    | before removing the account.
    |
    */
    'runner' => [
        'default_user_id' => (int) (env('PM_DEFAULT_RUNNER_USER_ID') ?: 1),

        /*
         | The work feed's due window (GET /crawlers-unprocessed,
         | CrawlerRepository::unprocessed()).
         |
         | The feed used to carry every pending queue with all its jobs. On
         | 2026-10-06/07 that was ~11,084 queues and ~394k jobs per poll; every
         | poll 500'd for ~16 h and the runner on ex63 started nothing, for any
         | experiment. It now sends a queue only when the runner can act on it:
         | start_at at or before now + lookahead_minutes, or overdue (no lower
         | bound -- the runner is the only thing that closes a stale queue, as
         | SKIPPED_TIMEDOUT, and only if it receives it), or processing (its
         | 180-minute timeout needs it), or reviewed (closed on sight), or a
         | start_at that is NULL or not 'Y-m-d H:i:s' (counted as due, as before).
         |
         | Selection is by time, not a fixed top-N: a top-N stalls once more
         | than N are due at once. Waiting rows are ranked runnable first
         | (start_at no more than stale_after_minutes overdue), then stale, then
         | unparseable, then NULL start_at (the runner throws on those), oldest
         | first within each. A poll sends at most max_jobs_per_poll jobs -- the
         | memory guard -- and at most max_queues_per_poll waiting queues, a
         | secondary bound. Processing rows are never cut, the first waiting row
         | is always sent, and any poll that holds work back logs it (error when
         | runnable work is late, warning otherwise). Never a silent cap.
         |
         | Memory: ~18.5 KB per job measured on this endpoint (jobs with 2
         | behaviours and 1 report; experiment 189 averages 2.5 behaviours a
         | job), on top of the bench's ~50 MB baseline. 10,000 jobs is ~185 MB, ~235 MB
         | of php.ini's 512M. The 20,000 ceiling is ~370 MB, ~420 MB with the
         | baseline: ~80% of 512M before processing rows (never cut) and an
         | oversized first row add to it. Raise memory_limit before raising the
         | budget past ~15,000.
         |
         | WARNING -- lookahead_minutes must be at least the largest
         | runner.UpdateInterval of any runner polling this API, plus margin
         | (code default 15, ex63 tracked config 1), or queues start late.
         | stale_after_minutes must exceed runner.MaxRedoMinutes (45), or rows
         | the runner can still run rank as stale. Its default is 46, exactly
         | that: a row more overdue at poll time can only be skipped by the
         | runner, so it must not take the place of one that can still run.
         | If a runner is configured with a larger MaxRedoMinutes, raise this
         | to match it plus one.
         |
         | A missing, non-numeric or non-positive value means the default below
         | (so a config cache built before these keys existed is harmless), and
         | the code clamps: lookahead >= 15, stale_after >= 46,
         | max_queues_per_poll in 1..1000, max_jobs_per_poll in 1..20000.
         */
        'lookahead_minutes' => (int) (env('PM_RUNNER_LOOKAHEAD_MINUTES') ?: 30),
        'stale_after_minutes' => (int) (env('PM_RUNNER_STALE_AFTER_MINUTES') ?: 46),
        'max_queues_per_poll' => (int) (env('PM_RUNNER_MAX_QUEUES_PER_POLL') ?: 300),
        'max_jobs_per_poll' => (int) (env('PM_RUNNER_MAX_JOBS_PER_POLL') ?: 10000),
    ],
];
