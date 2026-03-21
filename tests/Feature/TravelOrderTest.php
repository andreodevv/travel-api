<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\TravelOrder;
use App\Enums\TravelOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Class TravelOrderTest
 * * Testes de Integração (Feature) para o módulo de Pedidos de Viagem.
 * Esta classe valida o ciclo de vida completo de um pedido, incluindo:
 * - Autenticação e Autorização (Policies)
 * - Persistência e Validação de Dados
 * - Regras de Negócio e Transição de Status
 * - Filtros Avançados e Busca Global
 * - Disparo de Notificações
 */
class TravelOrderTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // BLOCO 1: CRIAÇÃO E VALIDAÇÕES (Store)
    // =========================================================================

    /**
     * Valida se um usuário autenticado pode criar um pedido e se a
     * resposta segue o contrato de API definido no Resource.
     * @test
     */
    public function test_a_user_can_create_a_travel_order_and_resource_returns_correct_structure(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/travel-orders', [
            'origin' => 'Belo Horizonte',
            'destination' => 'Florianópolis',
            'departure_date' => now()->addDays(1)->format('Y-m-d'),
            'return_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('travel_orders', [
            'origin' => 'Belo Horizonte',
            'destination' => 'Florianópolis'
        ]);
        
        $response->assertJsonStructure([
            'data' => [
                'id',
                'order_number',
                'requester_name',
                'origin',
                'destination',
                'departure_date',
                'return_date',
                'status',
                'created_at'
            ]
        ]);
    }

    /**
     * Valida a flexibilidade do pedido para trechos apenas de ida (sem return_date).
     * @test
     */
    public function test_a_user_can_create_a_one_way_travel_order_without_return_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/travel-orders', [
            'origin' => 'São Paulo',
            'destination' => 'Rio de Janeiro',
            'departure_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('travel_orders', [
            'destination' => 'Rio de Janeiro',
            'return_date' => null 
        ]);

        $response->assertJsonPath('data.return_date', null);
    }

    /**
     * Garante que os campos obrigatórios sejam validados antes da persistência.
     * @test
     */
    public function test_it_validates_required_fields_when_creating(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/travel-orders', []);

        $response->assertStatus(422);
        
        $response->assertJsonValidationErrors([
            'origin', 
            'destination', 
            'departure_date'
        ]);
    }

    /**
     * Valida a regra de consistência cronológica: retorno não pode ser antes da ida.
     * @test
     */
    public function test_return_date_must_be_after_departure_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/v1/travel-orders', [
            'origin' => 'Curitiba',
            'destination' => 'São Paulo',
            'departure_date' => now()->addDays(5)->format('Y-m-d'),
            'return_date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['return_date']);
    }

    // =========================================================================
    // BLOCO 2: VISUALIZAÇÃO E AUTORIZAÇÃO (Index & Show)
    // =========================================================================

    /**
     * Valida o Route Model Binding customizado (ID técnico vs Número de Negócio).
     * @test
     */
    public function test_it_can_fetch_order_by_ulid_or_order_number(): void
    {
        $user = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $user->id]);

        // Consulta por ULID
        $this->actingAs($user, 'api')
             ->getJson("/api/v1/travel-orders/{$order->id}")
             ->assertStatus(200);

        // Consulta por Order Number
        $this->actingAs($user, 'api')
             ->getJson("/api/v1/travel-orders/{$order->order_number}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $order->id);
    }

    /**
     * Garante a privacidade dos dados: um usuário não acessa pedidos de terceiros.
     * @test
     */
    public function test_a_user_can_only_view_their_own_orders(): void
    {
        $hacker = User::factory()->create();
        $victim = User::factory()->create();
        
        $victimOrder = TravelOrder::factory()->create(['user_id' => $victim->id]);

        $response = $this->actingAs($hacker, 'api')->getJson("/api/v1/travel-orders/{$victimOrder->id}");

        $response->assertStatus(403);
    }

    /**
     * Valida o escopo de listagem para usuários comuns (Tenant Isolation).
     * @test
     */
    public function test_a_user_sees_only_their_orders_in_the_list(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        TravelOrder::factory()->count(3)->create(['user_id' => $user->id]);
        TravelOrder::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/travel-orders');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data'); 
    }
    
    /**
     * Valida o acesso administrativo: Admin visualiza todos os registros do sistema.
     * @test
     */
    public function test_an_admin_can_see_all_orders_from_all_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TravelOrder::factory()->create(['user_id' => $user1->id]);
        TravelOrder::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/travel-orders');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
    
    // =========================================================================
    // BLOCO 3: WORKFLOW DE STATUS E NOTIFICAÇÕES (Update)
    // =========================================================================

    /**
     * Valida que apenas administradores podem alterar o status de um pedido.
     * @test
     */
    public function test_a_regular_user_cannot_update_order_status(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::REQUESTED]);

        $response = $this->actingAs($regularUser, 'api')->patchJson("/api/v1/travel-orders/{$order->id}/status", [
            'status' => 'aprovado'
        ]);

        $response->assertStatus(403);
    }

    /**
     * Valida o fluxo completo de aprovação e o disparo da notificação correspondente.
     * @test
     */
    public function test_an_admin_can_approve_a_travel_order_and_notification_is_sent(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::REQUESTED]);

        $response = $this->actingAs($admin, 'api')->patchJson("/api/v1/travel-orders/{$order->id}/status", [
            'status' => 'aprovado'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(TravelOrderStatus::APPROVED, $order->fresh()->status);

        Notification::assertSentTo(
            [$order->user],
            \App\Notifications\OrderStatusChangedNotification::class
        );
    }

    /**
     * Valida o fluxo de cancelamento administrativo e disparo de notificação.
     * @test
     */
    public function test_an_admin_can_cancel_a_requested_travel_order_and_notification_is_sent(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::REQUESTED]);

        $response = $this->actingAs($admin, 'api')->patchJson("/api/v1/travel-orders/{$order->id}/status", [
            'status' => 'cancelado'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(TravelOrderStatus::CANCELED, $order->fresh()->status);

        Notification::assertSentTo(
            [$order->user],
            \App\Notifications\OrderStatusChangedNotification::class
        );
    }

    /**
     * Valida a imutabilidade de pedidos aprovados: não podem ser cancelados.
     * @test
     */
    public function test_an_admin_cannot_cancel_an_already_approved_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::APPROVED]);

        $response = $this->actingAs($admin, 'api')->patchJson("/api/v1/travel-orders/{$order->id}/status", [
            'status' => 'cancelado'
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Não é possível cancelar um pedido já aprovado.');
    }

    /**
     * Garante que a API rejeite valores de status que não pertençam ao Enum.
     * @test
     */
    public function test_it_validates_if_status_is_a_valid_enum_value(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create();

        $response = $this->actingAs($admin, 'api')->patchJson("/api/v1/travel-orders/{$order->id}/status", [
            'status' => 'status_inventado'
        ]);

        $response->assertStatus(422); 
    }

    // =========================================================================
    // BLOCO 4: FILTROS E BUSCA (Query Scopes)
    // =========================================================================

    /**
     * Valida a funcionalidade de filtragem por status na listagem.
     * @test
     */
    public function test_it_can_filter_orders_by_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        TravelOrder::factory()->count(2)->create(['status' => TravelOrderStatus::REQUESTED]);
        TravelOrder::factory()->create(['status' => TravelOrderStatus::APPROVED]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/travel-orders?status=aprovado');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('aprovado', $response->json('data.0.status'));
    }

    /**
     * Valida a filtragem por intervalo de datas (período de viagem).
     * @test
     */
    public function test_it_can_filter_orders_by_date_range(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        TravelOrder::factory()->create(['departure_date' => '2026-05-10']);
        TravelOrder::factory()->create(['departure_date' => '2026-05-15']);
        TravelOrder::factory()->create(['departure_date' => '2026-06-20']);

        $response = $this->actingAs($admin, 'api')->getJson('/api/v1/travel-orders?start_date=2026-05-01&end_date=2026-05-18');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    /**
     * Valida o motor de busca global (Origem, Destino e Nome do Requisitante).
     * @test
     */
    public function test_it_can_search_globally_by_origin_destination_or_user_name(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $user1 = User::factory()->create(['name' => 'Carlos Silva']);
        $user2 = User::factory()->create(['name' => 'Ana Souza']);

        TravelOrder::factory()->create([
            'user_id' => $user1->id,
            'origin' => 'São Paulo',
            'destination' => 'Rio de Janeiro'
        ]);

        TravelOrder::factory()->create([
            'user_id' => $user2->id,
            'origin' => 'Salvador',
            'destination' => 'Recife'
        ]);

        // Busca por ORIGEM
        $this->actingAs($admin, 'api')
             ->getJson('/api/v1/travel-orders?search=São Paulo')
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.origin', 'São Paulo');

        // Busca por NOME DO USUÁRIO
        $this->actingAs($admin, 'api')
             ->getJson('/api/v1/travel-orders?search=Ana Souza')
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.requester_name', 'Ana Souza');
             
        // Busca por termo inexistente
        $this->actingAs($admin, 'api')
             ->getJson('/api/v1/travel-orders?search=Miami')
             ->assertJsonCount(0, 'data');
    }

    // =========================================================================
    // BLOCO 5: AUDITORIA E COMPLIANCE (Audit Logs)
    // =========================================================================

    /**
     * Valida que um administrador pode visualizar o histórico de alterações.
     * @test
     */
    public function test_an_admin_can_view_audit_logs_for_any_travel_order(): void
    {
        // 1. Liga a auditoria ANTES do Model fazer o boot
        config(['audit.console' => true]); 

        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create();

        // 2. Manipulação do Tempo (Time Travel): 
        // Avançamos 1 segundo para garantir que o evento "updated" seja 
        // cronologicamente mais recente que o "created", evitando colisão no latest()
        $this->travel(1)->second();

        // 3. Realizamos a alteração
        $order->update(['destination' => 'Nova York']);

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/v1/travel-orders/{$order->id}/audits");

        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'event',
                    'user' => ['name', 'id'],
                    'modifications' => ['old', 'new'],
                    'ip_address',
                    'created_at'
                ]
            ]
        ]);

        // O índice 0 agora é garantidamente a alteração (updated)
        $response->assertJsonPath('data.0.event', 'updated');
        $response->assertJsonPath('data.0.modifications.new.destination', 'Nova York');
    }

    /**
     * Valida que um usuário comum NÃO tem permissão para acessar logs de auditoria.
     * @test
     */
    public function test_a_regular_user_is_forbidden_from_viewing_audit_logs(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $order = TravelOrder::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/travel-orders/{$order->id}/audits");

        $response->assertStatus(403);
    }

    /**
     * Garante que o sistema registra corretamente quem foi o autor da alteração.
     * @test
     */
    public function test_audit_log_correctly_attributes_the_responsible_user(): void
    {
        // Força a observação da auditoria manualmente
        TravelOrder::observe(\OwenIt\Auditing\AuditableObserver::class);
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create(['status' => \App\Enums\TravelOrderStatus::REQUESTED]);

        // Limpa auditorias antigas geradas pelo factory
        $order->audits()->delete();

        // Admin altera o status
        $this->actingAs($admin, 'api')->patchJson("/api/v1/travel-orders/{$order->id}/status", [
            'status' => 'aprovado'
        ])->assertStatus(200); // Trava de segurança inline para garantir que o PATCH funcionou

        $response = $this->actingAs($admin, 'api')
            ->getJson("/api/v1/travel-orders/{$order->id}/audits");

        $response->assertStatus(200);

        // O evento 'updated' deve estar atribuído ao Admin
        $response->assertJsonPath('data.0.event', 'updated');
        $response->assertJsonPath('data.0.user.id', $admin->id);
        $response->assertJsonPath('data.0.modifications.new.status', 'aprovado');
    }
}
