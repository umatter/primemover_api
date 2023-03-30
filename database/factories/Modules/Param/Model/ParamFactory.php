<?php

namespace Database\Factories\Modules\Param\Model;

use App\Modules\Param\Model\Param;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParamFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Param::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
        ];
    }
}
