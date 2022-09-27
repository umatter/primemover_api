<?php

namespace Database\Factories;

use App\Modules\Proxy\Model\Proxy;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProxyFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Proxy::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->lastName,
            'description' => $this->faker->text,
            'hostname' => $this->faker->randomElement(['my.proxy.com', 'your.proxy.com', 'some.proxy.net']),
            'port' => $this->faker->randomElement([8080, 8181, 8280]),
            'username' => 'proxyuser',
            'password' => $this->faker->word,
            'active' => $this->faker->randomElement([1, 0]),
        ];
    }
}
