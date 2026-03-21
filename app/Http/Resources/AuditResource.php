<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AuditResource
 * @mixin \OwenIt\Auditing\Models\Audit
 */
class AuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'user' => [
                'name' => $this->user?->name ?? 'Sistema',
                'id'   => $this->user_id,
            ],
            'modifications' => [
                'old' => $this->old_values,
                'new' => $this->new_values,
            ],
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}