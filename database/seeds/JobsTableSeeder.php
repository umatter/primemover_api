<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobsTableSeeder extends Seeder
{
    public function run(): void
    {
        // $fake = factory(App\Models\Job::class, 12)->create();

        DB::table('jobs')->insert([
            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 1,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 1,
            ],
        ]);

        DB::table('jobs')->insert([
            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 2,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 2,
            ],
        ]);

        DB::table('jobs')->insert([
            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 3,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 3,
            ],
        ]);

        DB::table('jobs')->insert([
            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 4,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 4,
            ],
        ]);

        DB::table('jobs')->insert([
            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 5,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 5,
            ],
        ]);

        DB::table('jobs')->insert([
            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Enter text
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Visit job
            [
                'description' => 'Visit webpage',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Wait job
            [
                'description' => 'Wait job',
                'name' => 'Wait',
                'type' => 'waitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Visit job
            [
                'description' => 'Visit job',
                'name' => 'Visit',
                'type' => 'visitjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Enter field
            [
                'description' => 'Enter field',
                'name' => 'Enter',
                'type' => 'entertextfieldjob',
                'user_id' => 1,
                'queue_id' => 6,
            ],

            // Select result from search
            [
                'description' => 'Select and click',
                'name' => 'Select',
                'type' => 'singleselecturljob',
                'user_id' => 1,
                'queue_id' => 6,
            ],
        ]);
    }
}
