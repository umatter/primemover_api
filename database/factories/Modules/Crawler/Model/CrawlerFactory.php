<?php

namespace Database\Factories\Modules\Crawler\Model;

use App\Modules\Crawler\Model\Crawler;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrawlerFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Crawler::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->lastName,
            'description' => $this->faker->text(15),
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => 1,
        ];
    }
}
