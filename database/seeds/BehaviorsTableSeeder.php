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


        /**
         * Bot 1:
        Proxy:
        - geo location (geosurf location_code): US-MA-BOSTON
        - sticky ip: 30 minutes
        Tasks:
        - 1. visit: https://www.motherjones.com/
        - 2. visit: https://www.washingtonpost.com/
        - 3. visit: https://edition.cnn.com/
        - 4. visit: https://www.google.com/
        - 5. use google to search for: "climate crisis"
        - 6. select one result from the list (randomly)
        - 7. use google to search for: "lgbt americans"
        - 8. select one result from the list (randomly)
        - 9. use google to search for: "Kamala Harris"
         */

        //$fake = factory(App\Behavior::class, 100)->create();

        DB::table('behaviors')->insert([
            // Job parameters: download file
            [
                // 1. Visit job
                'name' => 'type',
                'description' => 'Visit webpage',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.motherjones.com/',
                'user_id' => 1,
                'job_id' => 1,
            ],

            // 2. Visit job
            [
                'name' => 'type',
                'description' => 'Visit webpage',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.washingtonpost.com/',
                'user_id' => 1,
                'job_id' => 2,
            ],

            // 3. Visit job
            [
                'name' => 'type',
                'description' => 'Visit webpage',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://edition.cnn.com/',
                'user_id' => 1,
                'job_id' => 3,
            ],

            // 4. Visit job
            [
                'name' => 'type',
                'description' => 'Visit webpage',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 4,
            ],
            [
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 4,
            ],

            // 5. Enter text
            [
                'name' => 'type',
                'description' => 'Enter field',
                'value' => 'enter_field',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate crisis',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                'name' => 'css',
                'description' => 'CSS Selector',
                'value' => 'search',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                'name' => 'xpath',
                'description' => 'XPath',
                'value' => '',
                'user_id' => 1,
                'job_id' => 5,
            ],

            // 6. Select result from search
            [
                'name' => 'type',
                'description' => 'Click',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 6,
            ],
            [
                'name' => 'target',
                'description' => 'Target link',
                'value' => 'random',
                'user_id' => 1,
                'job_id' => 6,
            ],

            // 7. Visit job
            [
                'name' => 'type',
                'description' => 'Visit webpage',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 7,
            ],
            [
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 7,
            ],

            // 8. Enter field
            [
                'name' => 'type',
                'description' => 'Enter field',
                'value' => 'enter_field',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'lgbt america',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'css',
                'description' => 'CSS Selector',
                'value' => 'search',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'xpath',
                'description' => 'XPath',
                'value' => '',
                'user_id' => 1,
                'job_id' => 8,
            ],

            // 9. Select result from search
            [
                'name' => 'type',
                'description' => 'Click',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'target',
                'description' => 'Target link',
                'value' => 'random',
                'user_id' => 1,
                'job_id' => 9,
            ],

            // 10. Visit job
            [
                'name' => 'type',
                'description' => 'Visit webpage',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 10,
            ],
            [
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 10,
            ],

            // 11. Enter field
            [
                'name' => 'type',
                'description' => 'Enter field',
                'value' => 'enter_field',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                'name' => 'css',
                'description' => 'CSS Selector',
                'value' => 'search',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                'name' => 'xpath',
                'description' => 'XPath',
                'value' => '',
                'user_id' => 1,
                'job_id' => 11,
            ],

            // 12. Select result from search
            [
                'name' => 'type',
                'description' => 'Click',
                'value' => 'visit_webpage_browserbar',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'target',
                'description' => 'Target link',
                'value' => 'random',
                'user_id' => 1,
                'job_id' => 12,
            ],
        ]);


        //$fake = factory(App\Behavior::class, 15)->create();
    }
}
