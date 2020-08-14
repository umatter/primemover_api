<?php

use Illuminate\Database\Seeder;

class CrawlersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fake = factory(App\Crawler::class, 1)->create();
    }
}
