<?php

namespace Database\Seeders;

use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class QueuesTableSeeder extends Seeder
{
    public function run(): void
    {
        Queue::factory()->count(100)->create([
            'start_at' => Carbon::now()->subMinutes(605)->toISOString(),
            'started_at' => Carbon::now()->subMinutes(604)->toISOString(),
        ]);
    }
}
