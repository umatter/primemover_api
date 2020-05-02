<?php

use Illuminate\Database\Seeder;

class ConfigurationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $configurations = factory(App\Configuration::class, 10)->create();
        /*
                DB::table('configurations')->insert([
                    [
                        'name' => 'Trulex',
                        'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                        'browser' => 'Chrome',
                        'region' => 'Europe',
                        'url' => 'https://google.com/',
                        'search_query' => 'Hello World',
                        'active' => 0
                    ],
                    [
                        'name' => 'Simplex',
                        'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                        'browser' => 'Chrome',
                        'region' => 'Europe',
                        'url' => 'https://google.com/',
                        'search_query' => 'Hello World',
                        'active' => 1
                    ]
                ]);
        */
    }
}
