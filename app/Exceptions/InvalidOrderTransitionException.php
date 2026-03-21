<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidOrderTransitionException extends Exception
{
    /**
     * Renderiza a exceção em uma resposta HTTP JSON.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage()
        ], 422);
    }
}