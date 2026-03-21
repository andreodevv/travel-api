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

    // =========================================================================
    // BLOCO 2: PERFIL DO USUÁRIO (/me) E CONTRATO DE DADOS
    // =========================================================================

    /**
     * Valida o "Caminho Feliz" do /me.
     * Engenharia: Garante que o DTO (UserResource) não seja quebrado no futuro.
     * O Front-end depende estritamente dessa estrutura para o Pinia funcionar.
     * @test
     */
    public function test_authenticated_user_can_fetch_their_profile_and_respects_resource_contract(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@email.com' // Usamos o e-mail que define o is_admin no Resource
        ]);

        // Simula uma requisição autenticada com o token JWT
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/me');

        $response->assertStatus(200);
        
        // Padrão Ouro: Validar a tipagem/estrutura de saída. 
        // Note que o Laravel empacota Resources dentro da chave 'data'.
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'permissions' => [
                    'is_admin'
                ],
                'created_at'
            ]
        ]);
        
        // Validação adicional de regra de negócio
        $response->assertJsonPath('data.permissions.is_admin', true);
    }

    /**
     * Trava de Segurança: Valida se o Middleware auth:api está bloqueando intrusos.
     * @test
     */
    public function test_unauthenticated_user_cannot_access_me_route(): void
    {
        // Requisição SEM o token
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    // =========================================================================
    // BLOCO 3: ENCERRAMENTO DE SESSÃO (/logout)
    // =========================================================================

    /**
     * Valida se a rota de logout encerra a sessão e retorna o HTTP Status correto.
     * @test
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/logout');

        $response->assertStatus(200);
        
        $response->assertJson([
            'message' => 'Sessão encerrada com sucesso.'
        ]);
    }
}