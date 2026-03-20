<?php

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // Recebemos o pedido de viagem no construtor
    public function __construct(public TravelOrder $order) {}

    /**
     * Define os canais de entrega (pode ser mail, database, slack, etc).
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Monta o E-mail.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusFormatado = strtoupper($this->order->status->value);

        return (new MailMessage)
            ->subject("Atualização no Pedido para {$this->order->destination}")
            ->greeting("Olá, {$this->order->requester_name}!")
            ->line("O status do seu pedido de viagem foi alterado para: **{$statusFormatado}**.")
            ->action('Visualizar Pedido', url("/api/travel-orders/{$this->order->id}"))
            ->line('Obrigado por utilizar nosso sistema corporativo!');
    }
}