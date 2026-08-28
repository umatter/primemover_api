<?php

namespace Tests\Feature\Modules\Report\Http\Controller\Api\v1;

use App\Modules\Report\Http\Controller\Api\v1\ReportController;
use App\Modules\Report\Http\Request\ReportStoreRequest;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Repository\ReportRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Report\Http\Controller\Api\v1\ReportController
 */
class ReportControllerTest extends TestCase
{
    /**
     * @covers ::index
     */
    public function test_index(): void
    {
        $controller = new ReportController(new ReportRepository(new Report()));
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $report) {
            $this->assertInstanceOf(Report::class, $report);
        }
    }

    /**
     * @covers ::store
     */
    public function test_store(): void
    {
        $job = \App\Modules\Job\Model\Job::factory()->create();

        $data = [
            'job_id' => $job->id,
            'mimetype' => 'application/zip',
            'filename' => 'Testarchiv',
            'path' => 'https://example.com/api/v1/file/1',
            'user_id' => $this->user->id,
            'active' => 1,
        ];

        $request = m::mock(ReportStoreRequest::class)->makePartial();
        $request->allows('validated')->andReturns($data);

        $controller = new ReportController(new ReportRepository(new Report()));
        $result = $controller->store($request);

        $this->assertDatabaseHas('reports', ['job_id' => $job->id, 'filename' => 'Testarchiv']);
        $this->assertInstanceOf(JsonResource::class, $result);
    }

    /**
     * @covers ::show
     */
    public function test_show(): void
    {
        $report = Report::factory()->create();

        $controller = new ReportController(new ReportRepository(new Report()));
        $result = $controller->show($report);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Report::class, $result->resource);
        $this->assertEquals($report->id, $result->resource->id);
    }

    /**
     * @covers ::destroy
     */
    public function test_destroy(): void
    {
        $report = Report::factory()->create();

        $controller = new ReportController(new ReportRepository(new Report()));
        $result = $controller->destroy($report);

        $this->assertDatabaseMissing('reports', $report->toArray());
        $this->assertIsBool($result);
    }
}
