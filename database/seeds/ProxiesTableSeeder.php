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
                    'name' => 'Multilogin Proxy 1',
                    'description' => 'Proxy for Multilogin',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-378870',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Multilogin Proxy 2',
                    'description' => 'Proxy for Multilogin',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-378870',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Multilogin Proxy 3',
                    'description' => 'Proxy for Multilogin',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-378870',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Multilogin Proxy 4',
                    'description' => 'Proxy for Multilogin',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-378870',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Multilogin Proxy 5',
                    'description' => 'Proxy for Multilogin',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-378870',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],
                [
                    'name' => 'Multilogin Proxy 6',
                    'description' => 'Proxy for Multilogin',
                    'user_id' => 1,
                    'hostname' => 'us-30m.geosurf.io',
                    'username' => '000000+US-MA-BOSTON+000000-378870',
                    'port' => 8000,
                    'password' => 'CHANGEME-password'
                ],

            ]
        );
    }
}
