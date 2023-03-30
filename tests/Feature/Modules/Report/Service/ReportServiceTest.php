<?php

namespace Tests\Feature\Modules\Report\Service;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Service\ReportService;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class ReportServiceTest extends TestCase
{
    /** @test */
    public function testGetData(): void
    {
        $service = new ReportService(new Report());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $report) {
            unset($report->created_at, $report->updated_at);

            $this->assertDatabaseHas('reports', $report->toArray());
            $this->assertInstanceOf(Report::class, $report);
        }
    }

    /** @test */
    public function testSave(): void
    {
        $job = Job::factory()->create();

        $data = [
            'job_id' => $job->id,
            'filecontent' => '123',
            'description' => '234',
            'mimetype' => 'application/zip',
            'filename' => 'Testarchiv',
            'path' => 'https://siaw.primemover.example/api/v1/file/1',
        ];

        $service = new ReportService(new Report());
        $result = $service->save($data);

        unset($data['path']);
        $this->assertDatabaseHas('reports', $data);
        $this->assertInstanceOf(Report::class, $result);
    }

    /** @test */
    public function testDelete(): void
    {
        $report = Report::factory()->create();

        $service = new ReportService(new Report());
        $result = $service->delete($report);

        $this->assertDatabaseMissing('reports', $report->toArray());
        $this->assertTrue($result);
    }
}
