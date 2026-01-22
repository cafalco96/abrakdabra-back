<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            ]
        );

        User::updateOrCreate(
            ['email' => 'gestor@abrakdabra.test'],
            [
                'name' => 'Gestor Eventos',
                'password' => Hash::make('gestor123'),
                'role' => UserRole::GESTOR,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'buyer1@abrakdabra.test'],
            [
                'name' => 'Buyer Test',
                'password' => Hash::make('buyer123'),
                'role' => UserRole::BUYER,
                'is_active' => true,
            ]
        );
    }
}
