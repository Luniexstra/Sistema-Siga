<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@siga.test',
        ], [
            'name' => 'Administrador SIGA',
            'role' => User::ROLE_ADMINISTRADOR,
            'email_verified_at' => now(),
            'password' => Hash::make('Admin12345'),
        ]);
    }
}
