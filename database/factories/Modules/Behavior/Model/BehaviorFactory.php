<?php

namespace Database\Factories\Modules\Behavior\Model;

use App\Modules\Behavior\Model\Behavior;
use App\Modules\Job\Model\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

class BehaviorFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Behavior::class;

    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomElement([1, 2]),
            'job_id' => Job::query()->where('active', 1)->get(['id'])->random(),
            'name' => $this->faker->randomElement(['type', 'url', 'other']),
            'description' => $this->faker->text,
            'value' => $this->faker->randomElement(['lorem', 'dolor', 'ipsum']),
            'active' => $this->faker->randomElement([1, 0]),
        ];
    }
}
