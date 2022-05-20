<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'job_id' => 1,
            'filecontent' => '123',
            'description' => '234',
            'mimetype' => 'application/zip',
            'filename' => 'Testarchiv',
            'path' => 'https://siaw.primemover.example/api/v1/file/1',
        ];
    }
}
