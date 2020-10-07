<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Experiment;
use Faker\Generator as Faker;

$factory->define(Experiment::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
    ];
});
