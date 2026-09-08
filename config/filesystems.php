<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'mock' => env('AWS_MOCK', false),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
        ],
        /*
         * The runner's job-data store. Despite the AWS_ naming this is the
         * LOCAL MinIO at s3.qamelab.org -- the system of record for every HAR,
         * page source and lambda payload the runner captures. No AWS account
         * is involved (orchestrator CLAUDE.md, "Where job data goes").
         *
         * 'endpoint' is what makes that true. Without it the S3 client talks
         * to real AWS, so every read fails however the credentials are set.
         * This disk had neither an endpoint nor a bucket until 2026-09-08,
         * which is why GET /file/{report} and GET /jobs/{id}/decision could
         * not serve a single object in production while their tests passed:
         * the tests use Storage::fake('s3runner') and so never touch a real
         * store. A faked disk cannot tell you the real one is unconfigured.
         *
         * The _RUNNER-suffixed keys fall back to the plain ones so an existing
         * deployment keeps working, while allowing this disk its own scoped,
         * read-only MinIO credentials -- which is what it should have, since
         * it only ever reads.
         */
        's3runner' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID_RUNNER', env('AWS_ACCESS_KEY_ID')),
            'secret' => env('AWS_SECRET_ACCESS_KEY_RUNNER', env('AWS_SECRET_ACCESS_KEY')),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET_RUNNER'),
            'endpoint' => env('AWS_ENDPOINT_RUNNER', env('AWS_ENDPOINT')),
            'url' => env('AWS_URL'),
            'mock' => env('AWS_MOCK', false),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
