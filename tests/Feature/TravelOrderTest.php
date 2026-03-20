<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TravelOrder;
use App\Enums\TravelOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TravelOrderTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // BLOCO 1: CRIAÇÃO E VALIDAÇÕES (Store)
    // =========================================================================

    /** @test */
    public function test_a_user_can_create_a_travel_order_and_resource_returns_correct_structure()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/travel-orders', [
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
        
        // Verifica a estrutura atualizada com ULID e Order Number
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

    /** @test */
    public function test_a_user_can_create_a_one_way_travel_order_without_return_date()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/travel-orders', [
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

    /** @test */
    public function test_it_validates_required_fields_when_creating()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/travel-orders', []);

        $response->assertStatus(422);
        
        $response->assertJsonValidationErrors([
            'origin', 
            'destination', 
            'departure_date'
        ]);
    }

    /** @test */
    public function test_return_date_must_be_after_departure_date()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/travel-orders', [
            'origin' => 'Curitiba',
            'destination' => 'São Paulo',
            'departure_date' => now()->addDays(5)->format('Y-m-d'),
            'return_date' => now()->addDays(1)->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['return_date']);
    }

    // =========================================================================
    // BLOCO 2: VISUALIZAÇÃO E POLICIES (Index & Show)
    // =========================================================================

    /** @test */
    public function test_it_can_fetch_order_by_ulid_or_order_number()
    {
        $user = User::factory()->create();
        $order = TravelOrder::factory()->create(['user_id' => $user->id]);

        // Consulta 1: Usando a Chave Primária (ULID)
        $this->actingAs($user, 'api')
             ->getJson("/api/travel-orders/{$order->id}")
             ->assertStatus(200);

        // Consulta 2: Usando a Chave de Negócio (Order Number)
        $this->actingAs($user, 'api')
             ->getJson("/api/travel-orders/{$order->order_number}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $order->id);
    }

    /** @test */
    public function test_a_user_can_only_view_their_own_orders()
    {
        $hacker = User::factory()->create();
        $victim = User::factory()->create();
        
        $victimOrder = TravelOrder::factory()->create(['user_id' => $victim->id]);

        $response = $this->actingAs($hacker, 'api')->getJson("/api/travel-orders/{$victimOrder->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function test_a_user_sees_only_their_orders_in_the_list()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        TravelOrder::factory()->count(3)->create(['user_id' => $user->id]);
        TravelOrder::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/travel-orders');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data'); 
    }
    
    /** @test */
    public function test_an_admin_can_see_all_orders_from_all_users()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        TravelOrder::factory()->create(['user_id' => $user1->id]);
        TravelOrder::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($admin, 'api')->getJson('/api/travel-orders');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
    
    // =========================================================================
    // BLOCO 3: REGRAS DE NEGÓCIO, STATUS E NOTIFICAÇÕES (UpdateStatus)
    // =========================================================================

    /** @test */
    public function test_a_regular_user_cannot_update_order_status()
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::REQUESTED]);

        $response = $this->actingAs($regularUser, 'api')->patchJson("/api/travel-orders/{$order->id}/status", [
            'status' => 'aprovado'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_an_admin_can_approve_a_travel_order_and_notification_is_sent()
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::REQUESTED]);

        $response = $this->actingAs($admin, 'api')->patchJson("/api/travel-orders/{$order->id}/status", [
            'status' => 'aprovado'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(TravelOrderStatus::APPROVED, $order->fresh()->status);

        Notification::assertSentTo(
            [$order->user],
            \App\Notifications\OrderStatusChangedNotification::class
        );
    }

    /** @test */
    public function test_an_admin_can_cancel_a_requested_travel_order_and_notification_is_sent()
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::REQUESTED]);

        $response = $this->actingAs($admin, 'api')->patchJson("/api/travel-orders/{$order->id}/status", [
            'status' => 'cancelado'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(TravelOrderStatus::CANCELED, $order->fresh()->status);

        Notification::assertSentTo(
            [$order->user],
            \App\Notifications\OrderStatusChangedNotification::class
        );
    }

    /** @test */
    public function test_an_admin_cannot_cancel_an_already_approved_order()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create(['status' => TravelOrderStatus::APPROVED]);

        $response = $this->actingAs($admin, 'api')->patchJson("/api/travel-orders/{$order->id}/status", [
            'status' => 'cancelado'
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Não é possível cancelar um pedido já aprovado.');
    }

    /** @test */
    public function test_it_validates_if_status_is_a_valid_enum_value()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = TravelOrder::factory()->create();

        $response = $this->actingAs($admin, 'api')->patchJson("/api/travel-orders/{$order->id}/status", [
            'status' => 'status_inventado'
        ]);

        $response->assertStatus(422); 
    }

    // =========================================================================
    // BLOCO 4: FILTROS E BUSCA (Query Scopes)
    // =========================================================================

    /** @test */
    public function test_it_can_filter_orders_by_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Cria 2 pedidos solicitados e 1 aprovado
        TravelOrder::factory()->count(2)->create(['status' => TravelOrderStatus::REQUESTED]);
        TravelOrder::factory()->create(['status' => TravelOrderStatus::APPROVED]);

        // Testa buscando só os aprovados
        $response = $this->actingAs($admin, 'api')->getJson('/api/travel-orders?status=aprovado');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data'); // Deve retornar apenas 1
        $this->assertEquals('aprovado', $response->json('data.0.status'));
    }

    /** @test */
    public function test_it_can_filter_orders_by_date_range()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Cria pedidos com datas específicas
        TravelOrder::factory()->create(['departure_date' => '2026-05-10']);
        TravelOrder::factory()->create(['departure_date' => '2026-05-15']);
        TravelOrder::factory()->create(['departure_date' => '2026-06-20']); // Fora do filtro

        // Filtra pedidos entre 01 e 18 de Maio
        $response = $this->actingAs($admin, 'api')->getJson('/api/travel-orders?start_date=2026-05-01&end_date=2026-05-18');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data'); // Deve retornar os 2 de Maio e ignorar o de Junho
    }

    /** @test */
    public function test_it_can_search_globally_by_origin_destination_or_user_name()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $user1 = User::factory()->create(['name' => 'Carlos Silva']);
        $user2 = User::factory()->create(['name' => 'Ana Souza']);

        // Pedido do Carlos (Origem: São Paulo, Destino: Rio)
        TravelOrder::factory()->create([
            'user_id' => $user1->id,
            'origin' => 'São Paulo',
            'destination' => 'Rio de Janeiro'
        ]);

        // Pedido da Ana (Origem: Salvador, Destino: Recife)
        TravelOrder::factory()->create([
            'user_id' => $user2->id,
            'origin' => 'Salvador',
            'destination' => 'Recife'
        ]);

        // Busca pela ORIGEM (Deve achar o do Carlos)
        $this->actingAs($admin, 'api')
             ->getJson('/api/travel-orders?search=São Paulo')
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.origin', 'São Paulo');

        // Busca pelo NOME DO USUÁRIO (Deve achar o da Ana)
        $this->actingAs($admin, 'api')
             ->getJson('/api/travel-orders?search=Ana Souza')
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.requester_name', 'Ana Souza');
             
        // Busca que não existe (Deve retornar vazio)
        $this->actingAs($admin, 'api')
             ->getJson('/api/travel-orders?search=Miami')
             ->assertJsonCount(0, 'data');
    }
}