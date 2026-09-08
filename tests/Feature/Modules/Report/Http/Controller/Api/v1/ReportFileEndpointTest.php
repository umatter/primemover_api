<?php

namespace Tests\Feature\Modules\Report\Http\Controller\Api\v1;

use App\Models\User;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * GET /api/v1/file/{report} -- one stored object, on its own.
 *
 * This endpoint answered 500 on every request it had ever received:
 * ReportController::getFile() called ReportRepository::getFile(), and no such
 * method existed on the concrete repository. Only the whole-job ZIP download
 * worked, so there was no way to fetch a single object -- which is why the GUI
 * could list a job's reports and offer no way to open one of them.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Report\Repository\ReportRepository
 */
class ReportFileEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::S3DISK);
    }

    /**
     * @covers ::getFile
     */
    public function test_it_serves_the_object_off_the_runner_disk(): void
    {
        $report = $this->reportWithObject($this->keyForJobUnderTest(), 'PK-the-bytes');

        $response = $this->fetch($report)->assertOk();

        $this->assertSame('PK-the-bytes', $response->streamedContent());
    }

    /**
     * The runner stamps 'primemover/s3' on a pointer row. It is a marker, not a
     * media type, and serving it as the Content-Type tells a browser nothing.
     *
     * @covers ::getFile
     */
    public function test_the_pointer_marker_is_not_served_as_a_content_type(): void
    {
        $key = $this->keyForJobUnderTest();
        $report = $this->reportWithObject($key, 'bytes');

        $response = $this->fetch($report)->assertOk();

        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));

        $disposition = (string) $response->headers->get('Content-Disposition');

        // Built by Symfony's makeDisposition, not by concatenating a
        // runner-written filename into a header.
        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertStringContainsString(basename($key), $disposition);
    }

    /**
     * A real media type on the row is respected.
     *
     * @covers ::getFile
     */
    public function test_a_real_mimetype_is_respected(): void
    {
        $report = $this->reportWithObject($this->keyForJobUnderTest(), 'bytes', 'application/zip');

        $this->fetch($report)->assertOk()->assertHeader('Content-Type', 'application/zip');
    }

    /**
     * @covers ::getFile
     */
    public function test_a_report_with_no_path_is_404(): void
    {
        $this->withExceptionHandling();

        $report = Report::factory()->create([
            'job_id' => $this->jobUnderTest()->id,
            'user_id' => $this->user->id,
            'path' => null,
        ]);

        $this->fetch($report)->assertNotFound();
    }

    /**
     * @covers ::getFile
     */
    public function test_a_path_the_store_has_nothing_at_is_404(): void
    {
        $this->withExceptionHandling();

        $report = Report::factory()->create([
            'job_id' => $this->jobUnderTest()->id,
            'user_id' => $this->user->id,
            'path' => Report::S3PREFIX.'reports/nothing-was-ever-written-here-'.$this->jobUnderTest()->id,
        ]);

        $this->fetch($report)->assertNotFound();
    }

    /**
     * The report policy walks job -> queue -> crawler -> experiment, and it has
     * to still apply now that the endpoint actually returns something.
     *
     * @covers ::getFile
     */
    public function test_another_researcher_cannot_fetch_the_object(): void
    {
        $this->withExceptionHandling();

        $report = $this->reportWithObject($this->keyForJobUnderTest(), 'bytes');

        $stranger = User::factory()->create();
        config([
            'primemover.isolation.enabled' => true,
            'primemover.isolation.service_account_ids' => [],
        ]);

        Passport::actingAs($stranger);
        $this->getJson('/api/v1/file/'.$report->id)->assertForbidden();
    }

    private function reportWithObject(string $key, string $contents, string $mimetype = Report::S3MIMETYPE): Report
    {
        Storage::disk(Report::S3DISK)->put($key, $contents);

        return Report::factory()->create([
            'job_id' => $this->jobUnderTest()->id,
            'user_id' => $this->user->id,
            'filename' => basename($key),
            'mimetype' => $mimetype,
            'filecontent' => null,
            'path' => Report::S3PREFIX.$key,
        ]);
    }

    /**
     * The runner names every object after the job it belongs to:
     * <prefix>-<jobtype>-<task>-<jobId>. Report::storageKey() requires that,
     * so a fixture key has to carry the real id.
     */
    private function keyForJobUnderTest(): string
    {
        return 'reports/static-jobdata-visitjob-youtube-'.$this->jobUnderTest()->id;
    }

    private function jobUnderTest(): Job
    {
        return Job::query()->where('queue_id', $this->queue->id)->firstOrFail();
    }

    private function fetch(Report $report): \Illuminate\Testing\TestResponse
    {
        Passport::actingAs($this->user);

        return $this->get('/api/v1/file/'.$report->id);
    }
}
