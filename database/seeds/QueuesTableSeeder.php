<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class QueuesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //$fake = factory(App\Queue::class, 1)->create();

        DB::table('queues')->insert([
            [
                'name' => 'Joblist 123',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                'user_id' => 1,
                'crawler_id' => 1,
                'start_at' => Carbon::now()->toISOString()

            ],
        ]);
    }

}
