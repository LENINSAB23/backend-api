<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::firstOrCreate(
            ['email' => 'admin@gruporino.pe'],
            [
                'name' => 'Administrador Grupo Rino',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '+51962000001',
                'avatar' => 'https://ui-avatars.com/api/?name=Admin+Rino&background=0D8ABC&color=fff',
            ]
        );

        // Repartidores (Drivers)
        User::firstOrCreate(
            ['email' => 'repartidor1@gruporino.pe'],
            [
                'name' => 'Juan Carlos Perez (Repartidor Rino 01)',
                'password' => Hash::make('driver123'),
                'role' => 'driver',
                'phone' => '+51962999001',
                'avatar' => 'https://ui-avatars.com/api/?name=Juan+Perez&background=E74C3C&color=fff',
            ]
        );

        User::firstOrCreate(
            ['email' => 'repartidor2@gruporino.pe'],
            [
                'name' => 'Carlos Mendoza (Repartidor Rino 02)',
                'password' => Hash::make('driver123'),
                'role' => 'driver',
                'phone' => '+51962999002',
                'avatar' => 'https://ui-avatars.com/api/?name=Carlos+Mendoza&background=2ECC71&color=fff',
            ]
        );

        // Cliente demo
        User::firstOrCreate(
            ['email' => 'cliente@gmail.com'],
            [
                'name' => 'Maria Fernandez',
                'password' => Hash::make('cliente123'),
                'role' => 'customer',
                'phone' => '+51962888111',
                'avatar' => 'https://ui-avatars.com/api/?name=Maria+Fernandez&background=F39C12&color=fff',
            ]
        );
    }
}
