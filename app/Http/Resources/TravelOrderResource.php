<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_name' => $this->user->name,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departure_date' => $this->departure_date->format('Y-m-d'),
            'return_date' => $this->return_date ? $this->return_date->format('Y-m-d') : null, 
            'status' => $this->status->value,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}