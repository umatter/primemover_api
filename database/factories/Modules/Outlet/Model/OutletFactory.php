<?php

namespace Database\Factories\Modules\Outlet\Model;

use App\Modules\Outlet\Model\Outlet;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutletFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Outlet::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
        ];
    }
}
