<?php

use Illuminate\Database\Seeder;

class ProxiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //$fake = factory(App\Proxy::class, 5)->create();

        DB::table('proxies')->insert([
                [
                    'name' => 'Proxy 01',
                    'description' => 'US-MA-BOSTON',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-982060',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Proxy 02',
                    'description' => 'US-MA-BOSTON',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-933738',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Proxy 03',
                    'description' => 'US-OK-OKLAHOMA_CITY',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-OK-OKLAHOMA_CITY+000000-902115',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Proxy 04',
                    'description' => 'US-OK-OKLAHOMA_CITY',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-OK-OKLAHOMA_CITY+000000-240288',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Proxy 05',
                    'description' => 'US-CA-SAN_FRANCISCO',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-CA-SAN_FRANCISCO+000000-502835',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Proxy 06',
                    'description' => 'US-CA-SAN_FRANCISCO',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-CA-SAN_FRANCISCO+000000-683540',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],

            ]
        );
    }
}
