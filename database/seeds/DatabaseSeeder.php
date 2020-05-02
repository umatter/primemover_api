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
        $this->call(UserTableSeeder::class);
        $this->call(ConfigurationsTableSeeder::class);
        $this->call(AgentTableSeeder::class);
        $this->call(ProxyTableSeeder::class);
        $this->call(QueryTableSeeder::class);
        $this->call(CrawlerTableSeeder::class);
        $this->call(PostTableSeeder::class);
    }
}
