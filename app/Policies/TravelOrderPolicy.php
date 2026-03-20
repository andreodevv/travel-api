<?php

namespace App\Policies;

use App\Models\TravelOrder;
use App\Models\User;

class TravelOrderPolicy
{
    /**
     * Se o usuário for admin, ele tem passe livre para tudo.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true;
        }

        return null; // Se não for admin, segue para as regras abaixo
    }

    public function viewAny(User $user): bool
    {
        // Retorna true porque o Controller é quem faz o filtro de ver 
        // "todos" (admin) ou "só os seus" (usuário comum) no scopeFilter.
        return true; 
    }

    public function view(User $user, TravelOrder $travelOrder): bool
    {
        // O Admin já passou no "before". Aqui só checamos o usuário comum.
        return $user->id === $travelOrder->user_id;
    }

    public function create(User $user): bool
    {
        // Qualquer usuário logado pode criar um pedido
        return true;
    }

    public function update(User $user, TravelOrder $travelOrder): bool
    {
        // Apenas o dono pode editar seus dados (enquanto status permitir)
        return $user->id === $travelOrder->user_id && $travelOrder->status->canCancel();
    }

    public function updateStatus(User $user, TravelOrder $travelOrder): bool
    {
        // Retorna false para usuários comuns. 
        // O Admin passará direto pelo método "before" lá em cima.
        return false;
    }

    public function delete(User $user, TravelOrder $travelOrder): bool
    {
        return false;
    }
}