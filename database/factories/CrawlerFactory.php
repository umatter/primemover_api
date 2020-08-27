<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Crawler;
use Faker\Generator as Faker;

$factory->define(Crawler::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
        'active' => 1,
        'proxy_id' => 1,
        'agent_id' => 1,
        'configuration_id' => 1,
    ];
});
