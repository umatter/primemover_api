<?php

namespace Tests\Feature\Config;

use Tests\TestCase;

/**
 * The s3runner disk must be able to reach a NON-AWS S3.
 *
 * The runner's job data lives in a local MinIO, not in AWS, so the disk needs
 * an explicit endpoint. It had neither an endpoint nor a bucket until
 * 2026-09-08, and the consequence was invisible for as long as it existed:
 * GET /file/{report} and GET /jobs/{id}/decision could not serve a single one
 * of the ~71,800 stored objects in production, while every test of them passed.
 *
 * They passed because they use Storage::fake('s3runner'). A faked disk answers
 * happily however the real one is configured, so no amount of coverage on those
 * endpoints could have caught it. That is what this file is for: it asserts the
 * SHAPE of the real disk config, which is the one thing a fake cannot stand in
 * for. It deliberately connects to nothing.
 *
 * @coversNothing
 */
class RunnerStorageDiskTest extends TestCase
{
    public function test_the_runner_disk_declares_an_endpoint(): void
    {
        $disk = config('filesystems.disks.s3runner');

        $this->assertIsArray($disk, 'the s3runner disk must exist');
        $this->assertArrayHasKey(
            'endpoint',
            $disk,
            'without an endpoint the S3 client talks to real AWS, and every read of the local MinIO fails'
        );
    }

    public function test_the_runner_disk_uses_path_style_addressing(): void
    {
        // MinIO serves buckets as a path segment, not as a subdomain; virtual
        // host style would resolve primemoverrunner.s3.qamelab.org, which does
        // not exist.
        $this->assertTrue(
            (bool) config('filesystems.disks.s3runner.use_path_style_endpoint'),
            'MinIO needs path-style addressing'
        );
    }

    public function test_the_runner_disk_sets_path_style_without_touching_the_other_disk(): void
    {
        // The plain s3 disk reads AWS_USE_PATH_STYLE_ENDPOINT too. Without an
        // override of its own, turning path-style on for MinIO means turning it
        // on for a disk that has nothing to do with MinIO. Every other key in
        // this disk already had that override; this one did not, and on ex63
        // the shared flag had to be flipped to deploy the disk at all.
        $config = file_get_contents(config_path('filesystems.php'));

        $this->assertStringContainsString(
            "env('AWS_USE_PATH_STYLE_ENDPOINT_RUNNER'",
            $config,
            'the runner disk should be able to set path-style on its own'
        );
        $this->assertStringContainsString(
            "env('AWS_USE_PATH_STYLE_ENDPOINT', true)",
            $config,
            'and still fall back to the shared value, so an existing deployment is unchanged'
        );
    }

    public function test_the_runner_disk_reads_its_own_env_keys_and_falls_back(): void
    {
        // The _RUNNER-suffixed variables let this disk hold a scoped read-only
        // credential of its own, while the fallback keeps an existing
        // deployment working unchanged.
        $config = file_get_contents(config_path('filesystems.php'));

        foreach ([
            'AWS_ENDPOINT_RUNNER',
            'AWS_BUCKET_RUNNER',
            'AWS_ACCESS_KEY_ID_RUNNER',
            'AWS_SECRET_ACCESS_KEY_RUNNER',
            'AWS_USE_PATH_STYLE_ENDPOINT_RUNNER',
        ] as $key) {
            $this->assertStringContainsString($key, $config, "the s3runner disk should honour {$key}");
        }

        $this->assertStringContainsString(
            "env('AWS_ENDPOINT_RUNNER', env('AWS_ENDPOINT'))",
            $config,
            'the runner endpoint should fall back to the shared AWS_ENDPOINT'
        );
    }
}
