<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UserResource
 * * Atua como uma camada de segurança (DTO/Boundary) para o modelo User.
 * Garante que dados sensíveis (password, tokens, hidden fields) nunca vazem pela API,
 * e centraliza a lógica de permissões, entregando um contrato estável para os clients (Vue/Mobile).
 * * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Mapeia os atributos do modelo User para o array de resposta.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Identificadores
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            
            // Engenharia de Permissões (Over-delivery):
            // O Client (Front-end) nunca deve inferir permissões com lógicas hardcoded.
            // Nós calculamos aqui e entregamos um objeto booleano pronto para o consumo do Pinia.
            'permissions' => [
                'is_admin' => $this->email === 'admin@email.com', // Ajuste para a sua regra de negócio real
            ],
            
            // Metadados
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}