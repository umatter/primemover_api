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

        //$fake = factory(App\Behavior::class, 100)->create();

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.motherjones.com/',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.washingtonpost.com/',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 4,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://edition.cnn.com/',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 6,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 7,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate crisis',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 10,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'lgbt america',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 14,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 15,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 17,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.foxnews.com/',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.breitbart.com/',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 4,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.nypost.com/',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 6,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 7,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'death tax',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 10,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate hoax',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 14,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 15,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 17,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.motherjones.com/',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.washingtonpost.com/',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 4,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://edition.cnn.com/',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 6,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 7,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate crisis',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 10,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'lgbt americans',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 14,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 15,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 17,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.foxnews.com/',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.breitbart.com/',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 4,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://nypost.com/',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 6,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 7,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'death tax',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 10,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate hoax',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 14,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 15,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 17,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.motherjones.com/',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.washingtonpost.com/',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 4,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://edition.cnn.com/',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 6,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 7,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate crisis',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 10,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'lgbt americans',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 14,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 15,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 17,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.foxnews.com/',
                'user_id' => 1,
                'job_id' => 1,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 2,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.breitbart.com/',
                'user_id' => 1,
                'job_id' => 3,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 4,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://nypost.com/',
                'user_id' => 1,
                'job_id' => 5,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 6,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 7,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'death tax',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 8,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 9,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 10,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 11,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate hoax',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 12,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 13,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 14,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 15,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 16,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 17,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 17,
            ],

        ]);


        //$fake = factory(App\Behavior::class, 15)->create();
    }
}
