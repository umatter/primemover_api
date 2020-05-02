<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Crawler;
use Faker\Generator as Faker;

$factory->define(Crawler::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
        'active' => $faker->randomElement([1, 0]),
        'proxy_id' => $faker->randomElement([1, 2]),
        'agent_id' => $faker->randomElement([1, 2]),
        'query_id' => $faker->randomElement([1, 2]),
        'configuration_id' => $faker->randomElement([1, 2]),
    ];
});
