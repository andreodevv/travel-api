<?php

namespace App\Enums;

enum TravelOrderStatus: string
{
    case REQUESTED = 'solicitado';
    case APPROVED = 'aprovado';
    case CANCELED = 'cancelado';

    public function canCancel(): bool
    {
        // Só pode cancelar se o status atual for 'solicitado'
        return $this === self::REQUESTED;
    }
}