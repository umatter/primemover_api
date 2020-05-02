<?php

use Illuminate\Database\Seeder;

class QueryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fake = factory(App\Query::class, 20)->create();
    }
}
