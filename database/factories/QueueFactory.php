<?php

namespace Database\Factories;

use App\Models\Crawler;
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
            'crawler_id' => Crawler::query()->inRandomOrder()->first()->id,
            'name' => $this->faker->name,
            'status_code' => $this->faker->randomElement(['success', 'failed']),
            'description' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
            'processing' => $this->faker->randomElement([1, 0]),
            'processed' => $this->faker->randomElement([1, 0]),
            'reviewed' => $this->faker->randomElement([1, 0]),
            'new' => $this->faker->randomElement([1, 0]),
        ];
    }
}
