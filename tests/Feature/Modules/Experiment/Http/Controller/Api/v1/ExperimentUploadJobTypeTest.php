<?php

namespace Tests\Feature\Modules\Experiment\Http\Controller\Api\v1;

use App\Modules\Job\Enum\JobType;
use App\Modules\Job\Model\Job;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The upload stores every job through the Job model, whose `type` is cast to
 * JobType, so a type missing from the enum fails the whole upload -- in test
 * mode too, and for every bot in it. The request itself only checks that
 * `type` is a string, so nothing earlier catches it.
 *
 * `clearstoragejob` is the runner's clear_storage job (runner PR #94):
 * clear the current origin's cookies and/or web storage, scope none | local
 * | session | all. pmspec compiles it to this type, and a design that clears
 * storage in one arm runs the same job with `scope: none` in the others, so
 * the arms do not differ in job count.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Experiment\Http\Controller\Api\v1\ExperimentController
 */
class ExperimentUploadJobTypeTest extends TestCase
{
    /**
     * @covers ::upload
     */
    public function test_an_upload_carrying_a_clear_storage_job_stores_it(): void
    {
        Passport::actingAs($this->user);

        $this->upload([$this->crawler([
            $this->job('visitjob', [['name' => 'url', 'value' => 'https://www.bing.com/']]),
            $this->job('clearstoragejob', [['name' => 'scope', 'value' => 'all']]),
        ])])->assertSuccessful();

        // setUp's factory jobs draw their type from JobType, which now includes
        // this one, so the job is found by the upload's description, not its type.
        $job = Job::query()->where('description', 'upload test')->where('type', JobType::clearStorage->value)->sole();
        $this->assertSame(JobType::clearStorage, $job->type);
        $this->assertSame(
            [['name' => 'scope', 'value' => 'all']],
            $job->behaviors()->get(['name', 'value'])->toArray(),
        );
    }

    /**
     * The control: the same upload with a type the enum does not list is
     * refused, and nothing of it is kept. Without this the test above would
     * also pass if the upload stopped checking types at all.
     *
     * @covers ::upload
     */
    public function test_an_upload_with_a_job_type_the_api_does_not_know_is_refused(): void
    {
        Passport::actingAs($this->user);
        $jobs = Job::query()->count();

        try {
            $this->upload([$this->crawler([
                $this->job('visitjob', [['name' => 'url', 'value' => 'https://www.bing.com/']]),
                $this->job('nosuchjob', []),
            ])]);
            $this->fail('an unknown job type was accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('upload', $e->errors());
        }

        $this->assertSame($jobs, Job::query()->count());
    }

    /**
     * As `pmspec upload` sends it: the crawlers as a JSON file in the `json`
     * field of a multipart request.
     *
     * @param array<int, array<string, mixed>> $crawlers
     */
    private function upload(array $crawlers): TestResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'crawlers');
        file_put_contents($path, json_encode($crawlers));

        return $this->post(
            '/api/v1/experiments/'.$this->experiment->id.'/upload',
            ['json' => new UploadedFile($path, 'crawlers.json', 'application/json', null, true)],
            ['Accept' => 'application/json'],
        );
    }

    /**
     * One crawler in the shape pmspec uploads, cut to what the pipeline reads.
     *
     * @param  array<int, array<string, mixed>> $jobs
     * @return array<string, mixed>
     */
    private function crawler(array $jobs): array
    {
        return [
            'name' => 'clear-storage bot',
            'description' => 'upload test',
            'active' => 1,
            'testing' => 0,
            'configuration' => [
                'name' => 'clear-storage configuration',
                'description' => 'upload test',
                'active' => 1,
                'params' => [
                    'pi' => 0, 'psi' => 0, 'alpha' => 0, 'tau' => 0, 'kappa' => 0, 'beta' => 0,
                    'search_terms' => [], 'media_outlet_urls' => [],
                ],
                'preferences' => [],
            ],
            'proxy' => [
                'name' => 'clear-storage proxy',
                'description' => 'upload test',
                'hostname' => 'proxy.invalid',
                'port' => 8080,
                'username' => 'user',
                'password' => 'secret',
                'active' => true,
                'type' => 'http',
            ],
            'agent' => [
                'name' => 'clear-storage agent',
                'description' => 'upload test',
                'identification' => 'mlx',
                'multilogin_id' => null,
                'multilogin_profile' => ['browser_type' => 'mimic', 'os_type' => 'windows'],
                'location' => 'US-IL-CHICAGO',
                'active' => 1,
            ],
            'queues' => [[
                'name' => 'clear-storage run',
                'description' => 'upload test',
                'start_at' => '2026-11-03 10:00:00',
                'timezone' => 'America/Chicago',
                'timezone_utc_offset' => '-06:00',
                'active' => true,
                'jobs' => $jobs,
            ]],
        ];
    }

    /**
     * @param  array<int, array{name: string, value: string}> $behaviors
     * @return array<string, mixed>
     */
    private function job(string $type, array $behaviors): array
    {
        return [
            'name' => $type,
            'type' => $type,
            'description' => 'upload test',
            'active' => true,
            'behaviors' => array_map(
                fn (array $b) => $b + ['description' => '', 'active' => true],
                $behaviors,
            ),
        ];
    }
}
