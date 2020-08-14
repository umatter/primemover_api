<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Job;
use Faker\Generator as Faker;

$factory->define(Job::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        //'queue_id' => $faker->randomElement([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]),
        'queue_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
        'active' => $faker->randomElement([1, 0]),
    ];
});
