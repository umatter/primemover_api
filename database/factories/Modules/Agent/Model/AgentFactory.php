<?php

namespace Database\Factories\Modules\Agent\Model;

use App\Modules\Agent\Model\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->lastName,
            'description' => $this->faker->text,
            'identification' => $this->faker->randomElement(['User-Agent: Multilogin', 'User-Agent: Mozilla/5.0', 'User-Agent: Mozilla/4.0', 'User-Agent: Mozilla/6.0']),
            'multilogin_id' => $this->faker->uuid,
            'active' => $this->faker->randomElement([1, 0]),
        ];
    }
}
