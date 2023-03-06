<?php

namespace Database\Seeders;

use App\Modules\Report\Model\Report;
use Illuminate\Database\Seeder;

class ReportsTableSeeder extends Seeder
{
    public function run(): void
    {
        Report::factory(1)->create();
    }
}
