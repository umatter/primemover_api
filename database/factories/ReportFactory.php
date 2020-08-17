<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Report;
use Faker\Generator as Faker;

$factory->define(Report::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'job_id' => 1,
        'description' => $faker->text,
        'path' => $faker->imageUrl(),
        'active' => 1,
    ];
});


