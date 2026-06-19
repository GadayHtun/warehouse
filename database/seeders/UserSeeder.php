<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@warehouse.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Sarah Supervisor',
            'email' => 'supervisor@warehouse.test',
            'password' => Hash::make('password'),
            'role' => 'supervisor',
        ]);

        User::create([
            'name' => 'Alex Agent',
            'email' => 'agent@warehouse.test',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        User::create([
            'name' => 'Jordan Supervisor',
            'email' => 'supervisor2@warehouse.test',
            'password' => Hash::make('password'),
            'role' => 'supervisor',
        ]);

        User::create([
            'name' => 'Taylor Agent',
            'email' => 'agent2@warehouse.test',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);
    }
}
