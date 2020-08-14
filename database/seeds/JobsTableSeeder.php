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
            // 1. Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 2. Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 3. Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 4. Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],


            // 5. Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 6. Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],


            // 7. Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 8. Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 9. Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 10. Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 11. Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 12. Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 13. Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 14. Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 15. Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],


            // 16. Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 17. Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // 18. Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1
            ],
        ]);

    }
}
