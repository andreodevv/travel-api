<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Usamos ULID para evitar exposição de IDs sequenciais e facilitar a portabilidade
            $table->ulid('id')->primary()->comment('Identificador único global (ULID)');
            
            $table->string('name')->comment('Nome completo do colaborador');
            $table->string('email')->unique()->comment('E-mail corporativo (único)');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Controle de Acesso (ACL)
            $table->boolean('is_admin')->default(false)
                ->comment('Flag de privilégio: true = Administrador, false = Solicitante');

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes()->comment('Data de desativação da conta (Soft Delete)');

            $table->comment('Tabela de usuários e gestores do sistema de viagens.');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            
            $table->foreignUlid('user_id')->nullable()->index(); 
            
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};