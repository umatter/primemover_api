<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrawlersTableSeeder extends Seeder
{
    public function run(): void
    {
        // $fake = factory(App\Models\Crawler::class, 6)->create();

        DB::table('crawlers')->insert(
            [
                [
                    'name' => 'Otto',
                    'description' => 'Ich bin ein Crawler Otto',
                    'user_id' => 1,
                    'experiment_id' => 1,
                    'proxy_id' => 1,
                    'agent_id' => 1,
                    'testing' => 1,
                    'configuration_id' => 1,
                ],
                [
                    'name' => 'Saban',
                    'description' => 'Ich bin ein Crawler Saban',
                    'user_id' => 1,
                    'experiment_id' => 1,
                    'proxy_id' => 2,
                    'agent_id' => 2,
                    'testing' => 1,
                    'configuration_id' => 1,
                ],
                [
                    'name' => 'Maria',
                    'description' => 'Ich bin ein Crawler Maria',
                    'user_id' => 1,
                    'experiment_id' => 1,
                    'proxy_id' => 3,
                    'agent_id' => 3,
                    'testing' => 1,
                    'configuration_id' => 1,
                ],
                [
                    'name' => 'Gerard',
                    'description' => 'Ich bin ein Crawler Bukva',
                    'user_id' => 1,
                    'experiment_id' => 1,
                    'proxy_id' => 4,
                    'agent_id' => 4,
                    'testing' => 1,
                    'configuration_id' => 1,
                ],
                [
                    'name' => 'Stabilo',
                    'description' => 'Ich bin ein Crawler Stablo',
                    'user_id' => 1,
                    'experiment_id' => 1,
                    'proxy_id' => 5,
                    'agent_id' => 5,
                    'testing' => 1,
                    'configuration_id' => 1,
                ],
                [
                    'name' => 'Flyer',
                    'description' => 'Ich bin ein Crawler Kesten',
                    'user_id' => 1,
                    'experiment_id' => 1,
                    'proxy_id' => 6,
                    'agent_id' => 6,
                    'testing' => 1,
                    'configuration_id' => 1,
                ],
            ]
        );
    }
}
