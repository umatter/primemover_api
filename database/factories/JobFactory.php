<?php

namespace Database\Factories;

use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            // 'queue_id' => $this->faker->randomElement([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]),
            'queue_id' => 1,
            'name' => $this->faker->lastName,
            'description' => $this->faker->text,
            'type' => $this->faker->word,
            'active' => $this->faker->randomElement([1, 0]),
        ];
    }
}
