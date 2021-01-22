<?php

use Illuminate\Database\Seeder;

class ExperimentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fake = factory(App\Experiment::class, 3)->create();

    }

}
