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
                'name' => 'Multilogin Proxy',
                'description' => 'Proxy for Multilogin',
                'user_id' => 1,
                'hostname' => 'us-30m.geosurf.io',
                'username' => '000000+US-MA-BOSTON+000000-378870',
                'port' => 8000,
                'password' => 'CHANGEME-password'
            ]
        );
    }
}
