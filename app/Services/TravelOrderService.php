<?php

namespace App\Services;

use App\Models\TravelOrder;
use App\Enums\TravelOrderStatus;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Exception;

class TravelOrderService
{
    public function create(array $data, string $userId): TravelOrder
    {
        $data['user_id'] = $userId;        
        $data['order_number'] = $this->generateUniqueOrderNumber();

        return TravelOrder::create($data);
    }

    /**
     * Gera um número de pedido curto, legível e absolutamente único.
     */
    private function generateUniqueOrderNumber(): string
    {
        // Alfabeto seguro: sem 0, O, 1, I, L para evitar confusão visual
        $safeAlphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        
        do {
            // Pega 8 caracteres aleatórios do nosso alfabeto seguro
            $code = substr(str_shuffle($safeAlphabet), 0, 8);
            $orderNumber = "TRV-{$code}"; // Ex: TRV-X7KP9M12
            
        // O loop só repete se, por um milagre, esse código já existir no banco
        } while (TravelOrder::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function updateStatus(TravelOrder $order, TravelOrderStatus $newStatus): TravelOrder
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