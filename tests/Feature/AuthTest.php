<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_can_login_with_correct_credentials_and_receive_token()
    {
        // Cria um usuário com a senha 'password' (hasheada automaticamente pela factory)
        $user = User::factory()->create([
            'email' => 'teste@email.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste@email.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
            'user' => [
                'id',
                'name',
                'email'
            ]
        ]);
    }

    /** @test */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'teste@email.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste@email.com',
            'password' => 'senha_errada_123',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Credenciais inválidas'
        ]);
    }

    /** @test */
    public function test_it_validates_required_fields_for_login()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}