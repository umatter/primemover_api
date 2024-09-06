<?php

namespace Database\Factories\Modules\Job\Model;

use App\Modules\Job\Enum\JobType;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'queue_id' => Queue::factory()->create()->id,
            'name' => $this->faker->lastName,
            'status_code' => $this->faker->randomElement(['failed', 'success']),
            'status_message' => $this->faker->randomElement(['Random', 'Message']),
            'type' => $this->faker->randomElement(JobType::values()),
            'active' => 1,
        ];
    }
}
