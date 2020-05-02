<?php

/** @var Factory $factory */

use App\Configuration;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;

$factory->define(Configuration::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
        'active' => $faker->randomElement([1, 0]),
    ];
});
