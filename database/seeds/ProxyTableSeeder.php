<?php

use Illuminate\Database\Seeder;

class ProxyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fake = factory(App\Proxy::class, 5)->create();
    }
}
