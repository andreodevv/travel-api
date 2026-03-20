<?php

namespace Tests\Unit;

use App\Enums\TravelOrderStatus;
use PHPUnit\Framework\TestCase;

class TravelOrderStatusTest extends TestCase
{
    /** @test */
    public function test_it_can_determine_if_order_is_cancelable()
    {
        $this->assertTrue(TravelOrderStatus::REQUESTED->canCancel());
        $this->assertFalse(TravelOrderStatus::APPROVED->canCancel());
        $this->assertFalse(TravelOrderStatus::CANCELED->canCancel());
    }
}