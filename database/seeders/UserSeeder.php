<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Ian Bigingi',
                'email' => 'ian@estateflow.co.ke',
                'phone' => '+254700000001',
                'role' => 'landlord',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Miriam Kilonzo',
                'email' => 'miriam@estateflow.co.ke',
                'phone' => '+254700000002',
                'role' => 'manager',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Daniel Mworia',
                'email' => 'daniel@estateflow.co.ke',
                'phone' => '+254700000003',
                'role' => 'accountant',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
