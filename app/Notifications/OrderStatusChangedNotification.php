<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TravelOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class OrderStatusChangedNotification
 * * Responsável por comunicar ao usuário mudanças críticas em seu pedido de viagem.
 * Implementa ShouldQueue para garantir que o envio de e-mails ocorra em background,
 * não impactando o tempo de resposta da API (Request/Response Cycle).
 */
class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param TravelOrder $order Instância do pedido que sofreu a alteração.
     */
    public function __construct(public TravelOrder $order) {}

    /**
     * Define os canais de entrega da notificação.
     * * @param object $notifiable O modelo User que receberá a notificação.
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Constrói a representação de e-mail da notificação.
     * * @param object $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = strtoupper((string) $this->order->status->value);

        return (new MailMessage)
            ->subject("Atualização: Pedido #{$this->order->order_number}")
            ->greeting("Olá, {$this->order->user->name}!")
            ->line("Gostaríamos de informar que o status do seu pedido de viagem para **{$this->order->destination}** foi atualizado.")
            ->line("Novo Status: **{$statusLabel}**.")
            // Redireciona para o detalhamento do pedido via Business Key (Order Number)
            ->action('Acompanhar Pedido', url("/api/travel-orders/{$this->order->order_number}"))
            ->line('Se você tiver dúvidas sobre esta alteração, entre em contato com o departamento de viagens.')
            ->salutation('Atenciosamente, Equipe de Logística');
    }
}