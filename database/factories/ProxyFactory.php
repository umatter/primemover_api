<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Proxy;
use Faker\Generator as Faker;

$factory->define(Proxy::class, function (Faker $faker) {
    return [
        'user_id' => 1,
        'name' => $faker->lastName,
        'description' => $faker->text,
        'hostname' => $faker->randomElement(['my.proxy.com', 'your.proxy.com', 'some.proxy.net']),
        'port' => $faker->randomElement([8080, 8181, 8280]),
        'username' => 'proxyuser',
        'password' => $faker->word,
        'active' => $faker->randomElement([1, 0]),
    ];
});
