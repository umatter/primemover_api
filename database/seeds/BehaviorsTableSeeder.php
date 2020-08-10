<?php

use Illuminate\Database\Seeder;

class BehaviorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $fake = factory(App\Behavior::class, 100)->create();

        DB::table('behaviors')->insert([
            // Job parameters: download file
            [
                'name' => 'type',
                'description' => 'Download file',
                'value' => 'download',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                'name' => 'url',
                'description' => 'The url where to get the file',
                'value' => 'https://via.placeholder.com/700x300',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                'name' => 'other',
                'description' => 'Additional params',
                'value' => 'x',
                'user_id' => 1,
                'job_id' => 1,
            ],
            // Job parameters: search term
            [
                'name' => 'type',
                'description' => 'Type a term into search field',
                'value' => 'search',
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                'name' => 'url',
                'description' => 'The url where to place the search',
                'value' => 'https://google.com',
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                'name' => 'term',
                'description' => 'The term to search',
                'value' => 'politics',
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                'name' => 'other',
                'description' => 'Additional param',
                'value' => 'y',
                'user_id' => 1,
                'job_id' => 2,
            ],
            // Job parameters: login
            [
                'name' => 'type',
                'description' => 'Login in to a page',
                'value' => 'search',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                'name' => 'url',
                'description' => 'The url where to place the search',
                'value' => 'https://google.com',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                'name' => 'term',
                'description' => 'The term to search',
                'value' => 'politics',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                'name' => 'other',
                'description' => 'Additional param',
                'value' => 'z',
                'user_id' => 1,
                'job_id' => 3,
            ],
        ]);


        //$fake = factory(App\Behavior::class, 15)->create();
    }
}
