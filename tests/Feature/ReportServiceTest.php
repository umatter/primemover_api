<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Http\Service\ReportService;
use App\Modules\Report\Model\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    protected $user;

    public function setUp():void 
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testGetData()
    {
        $service = new ReportService(new Report());
        $result = $service->getData();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $report) {
            unset($report->created_at);
            unset($report->updated_at);
            $this->assertDatabaseHas("reports", $report->toArray());
            $this->assertInstanceOf(Report::class, $report);
        }
    }

    /** @test */
    public function testSave()
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

        unset($data["path"]);
        $this->assertDatabaseHas("reports", $data);
        $this->assertInstanceOf(Report::class, $result);
    }

    /** @test */
    public function testDelete()
    {
        $report = Report::factory()->create();

        $service = new ReportService(new Report());
        $result = $service->delete($report);

        $this->assertDatabaseMissing("reports", $report->toArray());
        $this->assertTrue($result);
    }
}
