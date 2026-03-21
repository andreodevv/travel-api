<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreTravelOrderRequest
 * * Encapsula a lógica de autorização e validação para a criação de pedidos.
 * Garante a integridade dos dados antes que eles cheguem à camada de serviço,
 * aplicando regras cronológicas e de obrigatoriedade de campos.
 */
class StoreTravelOrderRequest extends FormRequest
{
    // =========================================================================
    // AUTORIZAÇÃO (Access Control)
    // =========================================================================

    /**
     * Determina se o usuário tem permissão para realizar esta requisição.
     * @return bool
     */
    public function authorize(): bool
    {
        // Retornamos true pois a autorização granular é gerida pela TravelOrderPolicy.
        return true;
    }

    // =========================================================================
    // REGRAS DE VALIDAÇÃO (Business Rules)
    // =========================================================================

    /**
     * Define as regras de validação aplicadas aos dados de entrada.
     * * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            
            // Impede a criação de viagens para o passado
            'departure_date' => 'required|date|after_or_equal:today',
            
            // Opcional, mas se enviado, deve ser logicamente após a partida
            'return_date' => 'nullable|date|after:departure_date', 
        ];
    }
}