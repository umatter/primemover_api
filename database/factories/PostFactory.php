<?php

namespace Database\Factories;

use App\Modules\Post\Model\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'title' => 'Title',
            'body' => 'Body',
        ];
    }
}
