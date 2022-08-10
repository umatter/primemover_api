<?php

namespace Database\Seeders;

use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JobsTableSeeder extends Seeder
{
    public function run(): void
    {
        Job::factory([
            'finished_at' => Carbon::now()->subMinutes(603)->toISOString(),
            'started_at' => Carbon::now()->subMinutes(604)->toISOString(),
        ])->count(500)->create();
    }
}
