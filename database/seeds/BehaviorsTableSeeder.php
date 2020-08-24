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
                'job_id' => 18,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 19,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.breitbart.com/',
                'user_id' => 1,
                'job_id' => 20,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 21,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.nypost.com/',
                'user_id' => 1,
                'job_id' => 22,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 23,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 24,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'death tax',
                'user_id' => 1,
                'job_id' => 25,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 25,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 25,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 26,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 26,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 26,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 27,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 28,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate hoax',
                'user_id' => 1,
                'job_id' => 29,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 29,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 29,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 30,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 30,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 30,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 31,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 32,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 33,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 33,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 33,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 34,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 34,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 34,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.motherjones.com/',
                'user_id' => 1,
                'job_id' => 35,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 36,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.washingtonpost.com/',
                'user_id' => 1,
                'job_id' => 37,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 38,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://edition.cnn.com/',
                'user_id' => 1,
                'job_id' => 39,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 40,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 41,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate crisis',
                'user_id' => 1,
                'job_id' => 42,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 42,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 42,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 43,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 43,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 43,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 44,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 45,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'lgbt americans',
                'user_id' => 1,
                'job_id' => 46,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 46,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 46,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 47,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 47,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 47,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 48,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 49,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 50,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 50,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 50,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 51,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 51,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 51,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.foxnews.com/',
                'user_id' => 1,
                'job_id' => 52,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 53,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.breitbart.com/',
                'user_id' => 1,
                'job_id' => 54,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 55,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://nypost.com/',
                'user_id' => 1,
                'job_id' => 56,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 57,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 58,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'death tax',
                'user_id' => 1,
                'job_id' => 59,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 59,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 59,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 60,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 60,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 60,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 61,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 62,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate hoax',
                'user_id' => 1,
                'job_id' => 63,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 63,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 63,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 64,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 64,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 64,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 65,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 66,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 67,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 67,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 67,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 68,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 68,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 68,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.motherjones.com/',
                'user_id' => 1,
                'job_id' => 69,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 70,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.washingtonpost.com/',
                'user_id' => 1,
                'job_id' => 71,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 72,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://edition.cnn.com/',
                'user_id' => 1,
                'job_id' => 73,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 74,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 75,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate crisis',
                'user_id' => 1,
                'job_id' => 76,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 76,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 76,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 77,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 77,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 77,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 78,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 79,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'lgbt americans',
                'user_id' => 1,
                'job_id' => 80,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 80,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 80,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 81,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 81,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 81,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 82,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 83,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 84,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 84,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 84,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 85,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 85,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 85,
            ],

        ]);

        DB::table('behaviors')->insert([
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.foxnews.com/',
                'user_id' => 1,
                'job_id' => 86,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 87,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.breitbart.com/',
                'user_id' => 1,
                'job_id' => 88,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 89,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://nypost.com/',
                'user_id' => 1,
                'job_id' => 90,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 91,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 92,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'death tax',
                'user_id' => 1,
                'job_id' => 93,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 93,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 93,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 94,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 94,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 94,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 95,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 96,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'climate hoax',
                'user_id' => 1,
                'job_id' => 97,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 97,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 97,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 98,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 98,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 98,
            ],
            [
                // Wait
                'name' => 'seconds',
                'description' => 'Wait time',
                'value' => 20,
                'user_id' => 1,
                'job_id' => 99,
            ],
            [
                // Visit
                'name' => 'url',
                'description' => 'Target URL',
                'value' => 'https://www.google.com/',
                'user_id' => 1,
                'job_id' => 100,
            ],
            [
                // Enter text
                'name' => 'text',
                'description' => 'Text to enter',
                'value' => 'Kamala Harris',
                'user_id' => 1,
                'job_id' => 101,
            ],
            [
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 101,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => "//input[@name='q']",
                'user_id' => 1,
                'job_id' => 101,
            ],
            [
                // Click
                'name' => 'selectionType',
                'description' => 'Type of selection',
                'value' => 'XPATH',
                'user_id' => 1,
                'job_id' => 102,
            ],
            [
                'name' => 'selector',
                'description' => 'CSS Selector',
                'value' => ".//div[@id='rso']/div/div/div/a",
                'user_id' => 1,
                'job_id' => 102,
            ],
            [
                'name' => 'decisionType',
                'description' => 'Decission type',
                'value' => "FIRST",
                'user_id' => 1,
                'job_id' => 102,
            ],

        ]);


        //$fake = factory(App\Behavior::class, 15)->create();
    }
}
