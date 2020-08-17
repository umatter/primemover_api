<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ReportsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fake = factory(App\Report::class, 50)->create();

    }

}
