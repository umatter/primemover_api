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
            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],


            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1
            ],

            // Select result from search
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
