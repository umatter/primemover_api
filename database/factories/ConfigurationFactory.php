<?php

namespace Database\Factories;

use App\Modules\Configuration\Model\Configuration;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfigurationFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Configuration::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->lastName,
            'active' => $this->faker->randomElement([1, 0]),
        ];
    }
}
