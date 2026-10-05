<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Trips\Application\TripService;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;

/**
 * One card on the board: the trip, its customers and its money.
 *
 * @mixin Trip
 */
class TripCardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $service = app(TripService::class);
        $money = $service->money($this->resource);

        $lrs = $this->receipts->where('type', TripReceipt::TYPE_LR)->values();

        return [
            'id' => (int) $this->id,
            'trip_no' => (int) $this->trip_no,
            'from_city' => $this->from_city,
            'to_city' => $this->to_city,
            'goods' => $this->goods,
            'lorry_no' => $this->lorry_no,
            'status_id' => (int) $this->status_id,
            'board_position' => (float) $this->board_position,
            'pickup_date' => $this->pickup_date?->format('Y-m-d'),
            'delivered_date' => $this->delivered_date?->format('Y-m-d'),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'customers' => $lrs
                ->map(fn ($receipt): array => [
                    'id' => $receipt->customer_id,
                    'name' => $receipt->customer_name,
                ])
                ->unique('id')
                ->values(),
            'lr_count' => $lrs->count(),
            'revenue' => $money['revenue'],
            'cost' => $money['cost'],
            'profit' => $money['profit'],
            'owner_balance' => $money['owner_balance'],
        ];
    }
}
