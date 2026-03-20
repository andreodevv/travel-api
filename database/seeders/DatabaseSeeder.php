<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar um Usuário Comum
        User::factory()->create([
            'name' => 'User Teste',
            'email' => 'user@email.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
        ]);

        // Criar um Usuário Administrador
        User::factory()->create([
            'name' => 'Admin Teste',
            'email' => 'admin@email.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);
    }
}
