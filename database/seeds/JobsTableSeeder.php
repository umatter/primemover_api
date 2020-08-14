<?php

use Illuminate\Database\Seeder;

class JobsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //$fake = factory(App\Job::class, 12)->create();


        DB::table('jobs')->insert([
            // Job parameters: download file
            [
                // 1. Visit job
                'description' => 'Visit webpage',
                'name' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 2. Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 3. Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 4. Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 5. Enter text
            [
                'description' => 'Enter field',
                'name' => 'enter_field',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 6. Select result from search
            [
                'description' => 'Select and click',
                'name' => 'select_single',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 7. Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 8. Enter field
            [
                'description' => 'Enter field',
                'name' => 'enter_field',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 9. Select result from search
            [
                'description' => 'Select and click',
                'name' => 'select_single',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 10. Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 11. Enter field
            [
                'description' => 'Enter field',
                'name' => 'enter_field',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 12. Select result from search
            [
                'description' => 'Click',
                'name' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'queue_id' => 1
            ],
        ]);

    }
}
