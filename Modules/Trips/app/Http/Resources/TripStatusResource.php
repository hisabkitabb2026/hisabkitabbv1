<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Trips\Models\TripStatus;

/**
 * @mixin TripStatus
 */
class TripStatusResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'colour' => $this->colour,
            'position' => (int) $this->position,
            'is_default' => (bool) $this->is_default,
            'is_closed' => (bool) $this->is_closed,
        ];
    }
}
