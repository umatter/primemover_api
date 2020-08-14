<?php

use Illuminate\Database\Seeder;

class AgentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //$fake = factory(App\Agent::class, 10)->create();
        // fec6f490-7c12-4551-afe3-163ea11a5b3b

        DB::table('agents')->insert([
                'name' => 'Multilogin',
                'description' => 'Multilogin',
                'user_id' => 1,
                'identification' => 'User-Agent: Multilogin',
                'multilogin_id' => '000000+US-MA-BOSTON+000000-378870',
            ]
        );
    }
}
