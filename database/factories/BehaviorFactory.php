<?php

/** @var Factory $factory */

use App\Behavior;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(Behavior::class, function (Faker $faker) {
    return [
        'user_id' => $faker->randomElement([1, 2]),
        'job_id' => $faker->randomElement([1, 2]),
        'name' => strtolower($faker->word),
        'description' => $faker->text,
        'value' => $faker->randomElement(['lorem', 1, 'ipsum', 2]),
        'active' => $faker->randomElement([1, 0]),
    ];
});
