<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'sitcexm-admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@sitc.edu',
                'password' => \Illuminate\Support\Facades\Hash::make('SITC@saudiarabia'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['username' => 'jotish'],
            [
                'name' => 'Teacher Jotish',
                'email' => 'jotish@sitc.edu',
                'password' => \Illuminate\Support\Facades\Hash::make('janina'),
                'role' => 'admin',
            ]
        );
    }
}
