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
         | Users who see everything. These are machine callers, not people.
         | The Java runner, the orchestrator CLI, the MCP server and the e2e
         | suite all authenticate as admin@primemover.example (user 1) today, so it has
         | to stay here until the runner is moved onto its own identity.
         */
        'service_account_ids' => array_values(array_filter(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', (string) env('PM_SERVICE_ACCOUNT_IDS', '1'))), 'strlen')
        ))),
    ],
];
