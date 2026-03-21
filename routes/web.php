<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;

/**
 * --------------------------------------------------------------------------
 * Health Check / Entry Point
 * --------------------------------------------------------------------------
 * Rota pública para verificação rápida do status da aplicação.
 * Utilizada por ferramentas de monitoramento (UptimeRobot, AWS Health Checks)
 * para validar a disponibilidade do serviço sem overhead de autenticação.
 */

Route::get('/', function (): JsonResponse {
    return response()->json([
        'service'     => 'Travel Orders API',
        'status'      => 'online',
        'version'     => '1.0.0',
        'framework'   => 'Laravel 13',
        'auth_method' => 'JWT (Stateless)',
        'timestamp'   => now()->toIso8601String(),
    ]);
});