<?php

namespace Database\Factories\Modules\Job\Model;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Job::class;

    public function definition(): array
    {
        $jobTypes = [
            'visitjob',
            'entertextfieldjob',
            'waitjob',
            'singleselecturljob',
            'testjob',
        ];

        return [
            'user_id' => 1,
            'queue_id' => Queue::query()->inRandomOrder()->first()->id,
            'name' => $this->faker->lastName,
            'status_code' => $this->faker->randomElement(['failed', 'success']),
            'status_message' => $this->faker->randomElement(['Random', 'Message']),
            'type' => $this->faker->randomElement($jobTypes),
            'active' => 1,
        ];
    }
}
