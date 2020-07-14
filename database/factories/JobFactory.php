<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Job;
use Faker\Generator as Faker;

$factory->define(Job::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'crawler_id' => $faker->randomElement([1, 2]),
        'description' => $faker->text,
        'active' => $faker->randomElement([1, 0]),
    ];
});
