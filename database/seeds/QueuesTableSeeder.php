<?php

use Illuminate\Database\Seeder;

class QueuesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fake = factory(App\Queue::class, 1)->create();
    }
}
