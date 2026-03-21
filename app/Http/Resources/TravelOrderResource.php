<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TravelOrderResource
 * * Transforma o modelo TravelOrder em uma estrutura JSON padronizada para a API.
 * Gerencia a formatação de datas, tradução de Enums e exposição de relacionamentos,
 * garantindo um contrato estável entre o Back-end e os consumidores (Front/Mobile).
 * * @mixin \App\Models\TravelOrder
 */
class TravelOrderResource extends JsonResource
{
    /**
     * Mapeia os atributos do modelo para o array de resposta.
     * * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identificadores (Técnico e de Negócio)
            'id' => $this->id, 
            'order_number' => $this->order_number,            
            
            // Dados do Solicitante
            'requester_name' => $this->user->name,
            
            // Itinerário
            'origin' => $this->origin,
            'destination' => $this->destination,
            
            // Datas formatadas para padrão ISO/Web
            'departure_date' => $this->departure_date->format('Y-m-d'),
            'return_date' => $this->return_date ? $this->return_date->format('Y-m-d') : null, 
            
            // Status  
            'status' => $this->status->value,
            'processed_at' => $this->processed_at?->toIso8601String(),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}