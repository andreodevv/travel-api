<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum TravelOrderStatus
 * * Define o conjunto finito de estados permitidos para um pedido de viagem.
 * Centraliza as regras de transição e capacidades de cada estado, 
 * garantindo integridade de dados na camada de aplicação e persistência.
 */
enum TravelOrderStatus: string
{
    case REQUESTED = 'solicitado';
    case APPROVED = 'aprovado';
    case CANCELED = 'cancelado';

    // =========================================================================
    // CAPACIDADES E REGRAS DE TRANSIÇÃO (State Capabilities)
    // =========================================================================

    /**
     * Determina se o estado atual permite o cancelamento do pedido.
     * * Regra de Negócio: Um pedido só pode ser cancelado ou editado enquanto
     * estiver no estado inicial de 'solicitado'. Uma vez aprovado, o fluxo
     * torna-se imutável para o usuário comum por questões de auditoria.
     * * @return bool
     */
    public function canCancel(): bool
    {
        return $this === self::REQUESTED;
    }
}