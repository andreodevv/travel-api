<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TravelOrderController;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * API Routes - v1
 * --------------------------------------------------------------------------
 * Estrutura de rotas da Travel Corporate API.
 * Autenticação: JWT (Stateless)
 * Proteção: Rate Limiting (Throttle) para prevenção de ataques DoS e Brute Force.
 */

Route::prefix('v1')->group(function () {

    /**
     * @group Autenticação
     * Rota de entrada para emissão de tokens. 
     * Aplicado Throttle restrito (3 tentativas por minuto) para evitar Brute Force.
     */
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:3,1')
        ->name('login');

    /**
     * @group Rotas Protegidas
     * Middleware 'auth:api' garante a validade do JWT.
     * Middleware 'throttle:60,1' limita o uso a 60 requisições por minuto por usuário.
     */
    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
        
        // Perfil e Logout
        Route::post('logout', [AuthController::class, 'logout'])
            ->name('logout');
        Route::get('me', [AuthController::class, 'me'])
            ->name('me');
        /**
         * CRUD de Pedidos de Viagem
         * O Resource automático mapeia: index, store, show, update, destroy.
         * Nota: O 'show' aceita tanto ULID quanto Order Number via Route Model Binding.
         */
        Route::apiResource('travel-orders', TravelOrderController::class);

        /**
         * Ações de Workflow
         * Rota específica para transição de estados (Aprovação/Cancelamento).
         * Restrito logicamente via TravelOrderPolicy.
         */
        Route::patch('travel-orders/{travelOrder}/status', [TravelOrderController::class, 'updateStatus'])
            ->name('travel-orders.update-status');    
        /**
         * Timeline de Auditoria
         * Rota estratégica para transparência de processos corporativos.
         * Apenas usuários com perfil administrativo podem acessar este histórico.
         */
        Route::get('travel-orders/{travelOrder}/audits', [TravelOrderController::class, 'audits'])
            ->name('travel-orders.audits');
    });
});