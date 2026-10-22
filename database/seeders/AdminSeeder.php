<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@rakit.test'],
            ['name' => 'Admin Rakit', 'password' => Hash::make('password'), 'role' => 'admin'],
        );

        User::updateOrCreate(
            ['email' => 'panitia@rakit.test'],
            ['name' => 'Panitia Demo', 'password' => Hash::make('password'), 'role' => 'customer'],
        );
    }
}
