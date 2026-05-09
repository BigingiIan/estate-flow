<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ian@estateflow.co.ke'],
            [
                'name' => 'Ian Bigingi',
                'phone' => '+254700000001',
                'role' => 'landlord',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
