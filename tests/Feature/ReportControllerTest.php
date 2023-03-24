<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Report\Http\Controller\Api\v1\ReportController;
use App\Modules\Report\Model\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * @internal
 */
class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        $controller = new ReportController();
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $report) {
            $this->assertInstanceOf(Report::class, $report);
        }
    }

    /** @test */
    public function testStore()
    {
        $data = new Request([
            'job_id' => 1,
            'mimetype' => 'jpg',
        ]);

        $controller = new ReportController();
        $result = $controller->store($data);

        $this->assertDatabaseHas('reports', $data->all());
        $this->assertInstanceOf(Response::class, $result);
    }

    /** @test */
    public function testShow()
    {
        $report = Report::factory()->create();

        $controller = new ReportController();
        $result = $controller->show($report);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Report::class, $result->resource);
        $this->assertEquals($report->id, $result->resource->id);
    }

    /** @test */
    public function testDestroy()
    {
        $report = Report::factory()->create();

        $controller = new ReportController();
        $result = $controller->destroy($report);

        $this->assertDatabaseMissing('reports', $report->toArray());
        $this->assertIsBool($result);
    }
}
