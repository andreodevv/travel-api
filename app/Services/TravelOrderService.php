<?php

namespace App\Services;

use App\Models\TravelOrder;
use App\Enums\TravelOrderStatus;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Exception;

class TravelOrderService
{
    public function create(array $data, int $userId): TravelOrder
    {
        return TravelOrder::create(array_merge($data, ['user_id' => $userId]));
    }

    public function updateStatus(TravelOrder $order, TravelOrderStatus $newStatus)
    {
        return DB::transaction(function () use ($order, $newStatus) {
            
            // Regra do Teste: Se for cancelar, verificar se já não foi aprovado
            if ($newStatus === TravelOrderStatus::CANCELED && !$order->canBeCanceled()) {
                throw new Exception("Não é possível cancelar um pedido já aprovado.");
            }

            $order->update(['status' => $newStatus]);

            // Dispara a notificação para o dono do pedido (DESCOMENTE ESTA LINHA)
            $order->user->notify(new OrderStatusChangedNotification($order));

            return $order;
        });
    }
}