<?php

namespace Database\Factories;

use App\Models\Experiment;
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
            'user_id' => 1,
            'name' => 'Experiment',
            'description' => 'Description',
        ];
    }
}
