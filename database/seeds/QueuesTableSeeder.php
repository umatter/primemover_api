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
                'name' => 'Joblist Crawler 1',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                'user_id' => 1,
                'crawler_id' => 1,
                'start_at' => Carbon::now()->addHours(2)->toISOString()
            ],
            [
                'name' => 'Joblist Crawler 2',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                'user_id' => 1,
                'crawler_id' => 2,
                'start_at' => Carbon::now()->addHours(4)->toISOString()
            ],
            [
                'name' => 'Joblist Crawler 3',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                'user_id' => 1,
                'crawler_id' => 3,
                'start_at' => Carbon::now()->addHours(6)->toISOString()
            ],
            [
                'name' => 'Joblist Crawler 4',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                'user_id' => 1,
                'crawler_id' => 4,
                'start_at' => Carbon::now()->addHours(8)->toISOString()
            ],
            [
                'name' => 'Joblist Crawler 5',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                'user_id' => 1,
                'crawler_id' => 5,
                'start_at' => Carbon::now()->addHours(10)->toISOString()
            ],
            [
                'name' => 'Joblist Crawler 6',
                'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                'user_id' => 1,
                'crawler_id' => 6,
                'start_at' => Carbon::now()->addHours(10)->toISOString()
            ],
        ]);
    }

}
