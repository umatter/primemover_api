<?php

use Illuminate\Database\Seeder;

class CrawlerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fake = factory(App\Crawler::class, 10)->create();
    }
}
