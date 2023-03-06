<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExperimentFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Experiment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::query()->inRandomOrder()->first()->id,
            'name' => $this->faker->name,
            'description' => $this->faker->sentence(),
        ];
    }
}
