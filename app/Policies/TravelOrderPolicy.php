<?php

namespace App\Policies;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TravelOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TravelOrder $travelOrder): bool
    {
        // O dono do pedido pode ver, ou um admin
        return $user->id === $travelOrder->user_id || $user->is_admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TravelOrder $travelOrder): bool
    {
        // Apenas o dpono pode editar seus dados (enquanto solicitado)
        return $user->id === $travelOrder->user_id && $travelOrder->status->canCancel();
    }

    /**
     * Determina se o usuário pode alterar o status do pedido.
     */
    public function updateStatus(User $user, TravelOrder $travelOrder): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TravelOrder $travelOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TravelOrder $travelOrder): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TravelOrder $travelOrder): bool
    {
        return false;
    }
}
