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
            'name'     => 'Ian Bigingi',
            'email'    => 'ian@estateflow.co.ke',
            'phone'    => '+254700000001',
            'role'     => 'landlord',
            'password' => Hash::make('password'),
        ]);
    }
}