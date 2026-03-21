<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TravelOrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * Class TravelOrderStatusTest
 * * Testes unitários puros para o Enum de Status.
 * Valida as regras de negócio de transição de estado de forma isolada,
 * sem dependência de banco de dados ou framework (Bootstrap rápido).
 */
class TravelOrderStatusTest extends TestCase
{
    // =========================================================================
    // VALIDAÇÃO DE CAPACIDADES (Business Rules)
    // =========================================================================

    /**
     * Testa se a lógica de cancelamento está correta para todos os estados.
     * * @test
     * @return void
     */
    public function test_it_can_determine_if_order_is_cancelable(): void
    {
        // Caminho Feliz: Solicitação pode ser cancelada
        $this->assertTrue(
            TravelOrderStatus::REQUESTED->canCancel(),
            'O status REQUESTED deve permitir cancelamento.'
        );

        // Caminhos de Exceção: Aprovado ou Já Cancelado não podem ser alterados
        $this->assertFalse(
            TravelOrderStatus::APPROVED->canCancel(),
            'Um pedido já APROVADO não deve permitir cancelamento.'
        );

        $this->assertFalse(
            TravelOrderStatus::CANCELED->canCancel(),
            'Um pedido já CANCELADO não deve permitir novo cancelamento.'
        );
    }
}