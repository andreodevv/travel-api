<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\TravelOrder;
use App\Enums\TravelOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TravelOrderFactory extends Factory
{
    public function definition(): array
    {
        $departure = now()->addDays(rand(1, 10));
        
        return [
            'user_id' => User::factory(), 
            'order_number' => 'TRV-' . date('Y') . '-' . strtoupper(Str::random(6)), 
            'origin' => fake()->city(),
            'destination' => fake()->city(),
            'departure_date' => $departure->format('Y-m-d'),
            'return_date' => fake()->boolean(70) ? $departure->copy()->addDays(rand(2, 10))->format('Y-m-d') : null,
            'status' => TravelOrderStatus::REQUESTED,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::APPROVED,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TravelOrderStatus::CANCELED,
        ]);
    }
}