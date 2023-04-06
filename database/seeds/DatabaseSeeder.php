<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (App::isProduction()) {
            return;
        }
        $this->call(UsersTableSeeder::class);
        $this->call(ExperimentsTableSeeder::class);
        $this->call(ConfigurationsTableSeeder::class);
        $this->call(AgentsTableSeeder::class);
        $this->call(ProxiesTableSeeder::class);
        $this->call(CrawlersTableSeeder::class);
        $this->call(PostsTableSeeder::class);
        $this->call(QueuesTableSeeder::class);
        $this->call(JobsTableSeeder::class);
        $this->call(BehaviorsTableSeeder::class);
        $this->call(ReportsTableSeeder::class);
    }
}
