<?php

namespace Database\Factories\Modules\Queue\Model;

use App\Modules\Queue\Model\Queue;
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
            'crawler_id' => 1,
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
