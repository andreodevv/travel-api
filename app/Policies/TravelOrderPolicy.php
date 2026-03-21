<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TravelOrder;
use App\Models\User;

/**
 * Class TravelOrderPolicy
 * * Camada de Autorização (ACL) da aplicação.
 * Define as permissões de acesso aos recursos de pedidos de viagem,
 * separando logicamente as capacidades de usuários comuns e administradores.
 */
class TravelOrderPolicy
{
    /**
     * Interceptador Global de Permissões.
     * * O Laravel executa este método antes de qualquer outra regra da Policy.
     * Administradores recebem autorização automática (Super User), simplificando
     * a lógica dos métodos subsequentes que focam apenas em usuários comuns.
     * * @param User $user O usuário autenticado.
     * @param string $ability A ação que está sendo validada.
     * @return bool|null Retorna true para autorizar, ou null para seguir as regras específicas.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true;
        }

        return null; 
    }

    /**
     * Determina se o usuário pode listar pedidos.
     * * @param User $user
     * @return bool Permitimos 'true' aqui pois a filtragem de visibilidade (Index)
     * é aplicada diretamente via Query Scopes no Controller/Model.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }

    /**
     * Determina se o usuário pode visualizar os detalhes de um pedido específico.
     * * @param User $user
     * @param TravelOrder $travelOrder
     * @return bool Autorizado se o usuário for o proprietário do recurso.
     */
    public function view(User $user, TravelOrder $travelOrder): bool
    {
        return $user->id === $travelOrder->user_id;
    }

    /**
     * Determina se o usuário pode visualizar a trilha de auditoria.
     * * @param User $user
     * @param TravelOrder $travelOrder
     * @return bool Retorna false para usuários comuns. 
     * O acesso é garantido exclusivamente para admins via 'before'.
     */
    public function viewAudits(User $user, TravelOrder $travelOrder): bool
    {
        return false;
    }

    /**
     * Determina se o usuário tem permissão para iniciar um novo pedido.
     * * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina se o usuário pode atualizar os dados de origem/destino do pedido.
     * * @param User $user
     * @param TravelOrder $travelOrder
     * @return bool Autorizado apenas para o proprietário enquanto o pedido 
     * ainda estiver em estado de solicitação (fluxo canCancel).
     */
    public function update(User $user, TravelOrder $travelOrder): bool
    {
        return $user->id === $travelOrder->user_id && $travelOrder->status->canCancel();
    }

    /**
     * Determina se o usuário pode alterar o estado (status) de um pedido.
     * * @param User $user
     * @param TravelOrder $travelOrder
     * @return bool Retorna false para usuários comuns. O acesso é garantido
     * exclusivamente para administradores via método 'before'.
     */
    public function updateStatus(User $user, TravelOrder $travelOrder): bool
    {
        return false;
    }

    /**
     * Determina se o usuário pode remover um pedido do sistema.
     * * @param User $user
     * @param TravelOrder $travelOrder
     * @return bool Ação desativada por regra de negócio (Soft Deletes podem ser aplicados via Admin).
     */
    public function delete(User $user, TravelOrder $travelOrder): bool
    {
        return false;
    }
}