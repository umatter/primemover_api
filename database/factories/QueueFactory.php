<?php

namespace Database\Factories;

use App\Models\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Queue::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            // 'crawler_id' => $faker->randomElement([1, 2, 3, 4, 5]),
            'crawler_id' => 1,
            'name' => $this->faker->lastName,
            'description' => $this->faker->text,
            'active' => $this->faker->randomElement([1, 0]),
        ];
    }
}
