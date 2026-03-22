<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TravelOrder;
use App\Enums\TravelOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
/**
 * Class TravelOrderService
 * * Orquestra a lógica de negócio para pedidos de viagem.
 * Garante a integridade dos dados, geração de identificadores de negócio 
 * e o disparo de efeitos colaterais (notificações/transações).
 */
class TravelOrderService
{
    /**
     * Cria um novo registro de pedido de viagem.
     * * @param array $data Dados validados do pedido.
     * @param string $userId Identificador ULID do usuário solicitante.
     * @return TravelOrder Instância do pedido persistida.
     */
    public function create(array $data, string $userId): TravelOrder
    {
        $data['user_id'] = $userId;        
        $data['order_number'] = $this->generateUniqueOrderNumber();

        return TravelOrder::create($data);
    }

    /**
     * Gera um identificador de pedido (Business Key) amigável e único.
     * * Utiliza um alfabeto Base32 customizado (Safe Alphabet) para remover 
     * caracteres visualmente ambíguos (0, O, 1, I, L), facilitando a 
     * comunicação verbal entre usuário e suporte.
     * * @return string Ex: TRV-X7KP9M2B
     */
    private function generateUniqueOrderNumber(): string
    {
        $safeAlphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        
        do {
            $code = substr(str_shuffle($safeAlphabet), 0, 8);
            $orderNumber = "TRV-{$code}";
            
        } while (TravelOrder::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Atualiza os dados de um pedido de viagem pendente.
     * * Apenas campos permitidos na requisição (origem, destino, datas) devem ser passados.
     *
     * @param TravelOrder $order Instância do pedido a ser atualizado.
     * @param array $data Dados validados para atualização.
     * @return TravelOrder Instância atualizada.
     * @throws InvalidOrderTransitionException Caso o pedido não esteja no status "solicitado".
     */
    public function update(TravelOrder $order, array $data): TravelOrder
    {
        // Trava de Estado Absoluta (State Guard)
        // Se alguém tentar editar via Postman um pedido já aprovado/cancelado, a API lança um HTTP 422.
        if ($order->status !== TravelOrderStatus::REQUESTED) {
            throw new InvalidOrderTransitionException('Edição negada. Apenas pedidos com status "solicitado" podem ser alterados.');
        }

        $order->update($data);

        // O pacote laravel-auditing que você instalou vai detectar o $order->update() 
        // e salvar um log de evento "updated" automaticamente com o 'old' e 'new'. Lindo, não?
        
        return $order;
    }
    
    /**
     * Atualiza o status do pedido garantindo atomicidade.
     * * @param TravelOrder $order Instância do pedido a ser atualizado.
     * @param TravelOrderStatus $newStatus Novo estado desejado.
     * @return TravelOrder Instância atualizada.
     * @throws Exception Caso a regra de transição de status seja violada.
     */
    public function updateStatus(TravelOrder $order, TravelOrderStatus $newStatus): TravelOrder
    {
        return DB::transaction(function () use ($order, $newStatus) {
            
            // Validação de Regra de Negócio: Pedidos aprovados são imutáveis para cancelamento.
            if ($newStatus === TravelOrderStatus::CANCELED && !$order->canBeCanceled()) {
                throw new InvalidOrderTransitionException('Não é possível cancelar um pedido já aprovado.');
            }

            // Atualizando o status para cancelado/aprovado e salvando a data/hora da atualização.
            $order->update([
                'status' => $newStatus, 
                'processed_at' => now()
            ]);

            // Notificação assíncrona para o solicitante.
            $order->user->notify(new OrderStatusChangedNotification($order));
            
            return $order;
        });
    }
}