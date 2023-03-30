<?php

namespace Tests\Feature\Modules\Report\Http\Controller\Api\v1;

use App\Modules\Report\Http\Controller\Api\v1\ReportController;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Repository\ReportRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
    public function testIndex(): void
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
    public function testStore(): void
    {
        $data = new Request([
            'job_id' => 1,
            'mimetype' => 'jpg',
        ]);

        $controller = new ReportController(new ReportRepository(new Report()));
        $result = $controller->store($data);

        $this->assertDatabaseHas('reports', $data->all());
        $this->assertInstanceOf(JsonResource::class, $result);
    }

    /**
     * @covers ::show
     */
    public function testShow(): void
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
    public function testDestroy(): void
    {
        $report = Report::factory()->create();

        $controller = new ReportController(new ReportRepository(new Report()));
        $result = $controller->destroy($report);

        $this->assertDatabaseMissing('reports', $report->toArray());
        $this->assertIsBool($result);
    }
}
