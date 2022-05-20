<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationsTableSeeder extends Seeder
{
    public function run(): void
    {
        Configuration::factory()->count(10)->create();
    }
}
