<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'Administrator',
                'email' => 'admin@primemover.example',
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ],
            [
                'name' => 'Primemover PY',
                'email' => 'researcher@primemover.example',
                'email_verified_at' => now(),
                'password' => '$2y$10$/cqGZzUBMYVe0J.RSdmCteArq8I0TQSXIwDPQEUBwmPXqSrUo0g/S',
                'remember_token' => 'CiBOkzrFVS'
            ],
            [
                'name' => 'Primemover Runner',
                'email' => 'runner@primemover.example',
                'email_verified_at' => now(),
                'password' => '$2y$10$/cqGZzUBMYVe0J.RSdmCteArq8I0TQSXIwDPQEUBwmPXqSrUo0g/S',
                'remember_token' => 'CiBOkzrFVS'
            ],
        ]);

        //$fake = factory(App\User::class, 5)->create();
    }
}
