<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Job;
use Faker\Generator as Faker;

$factory->define(Job::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
        'url' => $faker->randomElement(['nzz.ch', 'yahoo.com', 'google.com']),
        'query' => $faker->randomElement(['hello', 'there', 'who is it']),
        'active' => $faker->randomElement([1, 0]),
    ];
});
