<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class AuthTest
 * * Testes de Integração (Feature) para o módulo de Autenticação.
 * Garante que a emissão de tokens JWT e a validação de credenciais
 * estejam operando conforme os requisitos de segurança corporativa.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // BLOCO 1: AUTENTICAÇÃO E EMISSÃO DE TOKEN (Login)
    // =========================================================================

    /**
     * Valida o "Caminho Feliz" do login, garantindo que credenciais 
     * corretas retornem a estrutura esperada do JWT.
     * @test
     */
    public function test_user_can_login_with_correct_credentials_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'teste@email.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'teste@email.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);
    }

    /**
     * Valida a trava de segurança: credenciais erradas devem ser rejeitadas 
     * com HTTP 401 Unauthorized e mensagem padronizada.
     * @test
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'teste@email.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'teste@email.com',
            'password' => 'senha_errada_123',
        ]);

        $response->assertStatus(401);
        
        $response->assertJson([
            'error' => 'Credenciais inválidas'
        ]);
    }

    /**
     * Valida o FormRequest: a API deve barrar requisições malformadas 
     * antes mesmo de consultar o banco de dados (HTTP 422 Unprocessable Entity).
     * @test
     */
    public function test_it_validates_required_fields_for_login(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422);
        
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}