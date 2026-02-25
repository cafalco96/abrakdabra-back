<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@abrakdabra.test'],
            [
                'name' => 'Admin Abrakdabra',
                'password' => Hash::make('admin123'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'email_verified_at' => Carbon::now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'gestor@abrakdabra.test'],
            [
                'name' => 'Gestor Eventos',
                'password' => Hash::make('gestor123'),
                'role' => UserRole::GESTOR,
                'is_active' => true,
                'email_verified_at' => Carbon::now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'buyer1@abrakdabra.test'],
            [
                'name' => 'Buyer Test',
                'password' => Hash::make('buyer123'),
                'role' => UserRole::BUYER,
                'is_active' => true,
                'email_verified_at' => Carbon::now(),
            ]
        );
    }
}
