<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
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
