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
            [
                'name' => 'ML01',
                'description' => 'TEST_End2End_01',
                'user_id' => 1,
                'identification' => 'User-Agent: Multilogin',
                'multilogin_id' => 'fec6f490-7c12-4551-afe3-163ea11a5b3b',
            ],
            [
                'name' => 'ML02',
                'description' => 'TEST_End2End_02',
                'user_id' => 1,
                'identification' => 'User-Agent: Multilogin',
                'multilogin_id' => '7f4a5ee9-30d6-45f2-83ac-1acaa3750407',
            ],
            [
                'name' => 'ML03',
                'description' => 'TEST_End2End_03',
                'user_id' => 1,
                'identification' => 'User-Agent: Multilogin',
                'multilogin_id' => '671c4196-d68e-4dba-85f4-95575937bb27',
            ],
            [
                'name' => 'ML04',
                'description' => 'TEST_End2End_04',
                'user_id' => 1,
                'identification' => 'User-Agent: Multilogin',
                'multilogin_id' => 'c6e70352-aa50-4ce0-bd6c-f1d538f6a1dd',
            ],
            [
                'name' => 'ML05',
                'description' => 'TEST_End2End_05',
                'user_id' => 1,
                'identification' => 'User-Agent: Multilogin',
                'multilogin_id' => '13f20707-7acd-46bf-a455-f8ffe4cffb22',
            ],
            [
                'name' => 'ML06',
                'description' => 'TEST_End2End_06',
                'user_id' => 1,
                'identification' => 'User-Agent: Multilogin',
                'multilogin_id' => '9c7dc0a1-5070-4d4a-b2f6-58a1c1b5993a',
            ]
        ]);
    }
}
