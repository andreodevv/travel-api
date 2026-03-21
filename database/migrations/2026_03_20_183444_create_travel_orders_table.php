<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->ulid('id')->primary()->comment('Identificador técnico único (ULID)');
            
            // Business Key: A chave que o usuário usa para falar com o suporte
            $table->string('order_number')->unique()
                ->comment('Número de negócio amigável. Ex: TRV-ABC12345');

            // Relacionamento com integridade referencial
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete()
                ->comment('Vínculo com o usuário que realizou a solicitação');

            $table->string('origin')->comment('Cidade de partida da viagem');
            $table->string('destination')->comment('Cidade de destino da viagem');
            
            $table->date('departure_date')->comment('Data prevista para a ida');
            $table->date('return_date')->nullable()->comment('Data prevista para a volta (pode ser nulo em trechos só de ida)');
            
            // Status gerenciado via Enum no PHP
            $table->string('status')->comment('Estado do fluxo: solicitado, aprovado ou cancelado');
            $table->timestamp('processed_at')->nullable()->comment('Data/Hora em que o pedido saiu do estado solicitado (Aprovação ou Cancelamento)');

            $table->timestamps();
            $table->softDeletes()->comment('Rastro para auditoria de pedidos excluídos');

            $table->comment('Registros de solicitações de viagens e controle de workflow de aprovação.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};