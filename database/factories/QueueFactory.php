<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Queue;
use Faker\Generator as Faker;

$factory->define(Queue::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'crawler_id' => $faker->randomElement([1, 2, 3, 4, 5]),
        'name' => $faker->lastName,
        'description' => $faker->text,
        'active' => $faker->randomElement([1, 0]),
    ];
});
