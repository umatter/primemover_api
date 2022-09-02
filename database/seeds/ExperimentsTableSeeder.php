<?php

namespace Database\Seeders;

use App\Models\Experiment;
use Illuminate\Database\Seeder;

class ExperimentsTableSeeder extends Seeder
{
    public function run(): void
    {
        Experiment::factory()->count(15)->create();
    }
}
