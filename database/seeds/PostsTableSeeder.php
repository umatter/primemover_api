<?php

namespace Database\Seeders;

use App\Modules\Post\Model\Post;
use Illuminate\Database\Seeder;

class PostsTableSeeder extends Seeder
{
    public function run(): void
    {
        Post::factory(5)->create();
    }
}
