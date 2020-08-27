<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Agent;
use Faker\Generator as Faker;

$factory->define(Agent::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
        'identification' => $faker->randomElement(['User-Agent: Multilogin', 'User-Agent: Mozilla/5.0', 'User-Agent: Mozilla/4.0', 'User-Agent: Mozilla/6.0']),
        'multilogin_id' => $faker->uuid,
        'active' => $faker->randomElement([1, 0]),
    ];
});

