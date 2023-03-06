<?php

namespace Database\Seeders;

use App\Modules\Configuration\Model\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationsTableSeeder extends Seeder
{
    public function run(): void
    {
        Configuration::factory()->count(10)->create();
    }
}
