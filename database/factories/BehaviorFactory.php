<?php

/** @var Factory $factory */

use App\Behavior;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(Behavior::class, function (Faker $faker) {
    return [
        'user_id' => $faker->randomElement([1, 2]),
        'job_id' => App\Job::where('active', 1)->get(['id'])->random(),
        'name' => $faker->randomElement(['type', 'url', 'other']),
        'description' => $faker->text,
        'value' => $faker->randomElement(['lorem', 'dolor', 'ipsum']),
        'active' => $faker->randomElement([1, 0]),
    ];
});
