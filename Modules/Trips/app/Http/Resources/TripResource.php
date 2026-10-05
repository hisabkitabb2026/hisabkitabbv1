<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Trips\Application\TripService;
use Modules\Trips\Models\Trip;

/**
 * The whole trip: fields, status, documents, timeline, expenses, owner
 * payments and the computed money.
 *
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $service = app(TripService::class);

        return [
            'id' => (int) $this->id,
            'trip_no' => (int) $this->trip_no,
            'from_city' => $this->from_city,
            'to_city' => $this->to_city,
            'goods' => $this->goods,
            'weight' => $this->weight,
            'e_way_bill' => $this->e_way_bill,
            'pickup_date' => $this->pickup_date?->format('Y-m-d'),
            'delivered_date' => $this->delivered_date?->format('Y-m-d'),
            'lorry_no' => $this->lorry_no,
            'owner_party_id' => $this->owner_party_id,
            'driver_party_id' => $this->driver_party_id,
            'broker_party_id' => $this->broker_party_id,
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'status' => new TripStatusResource($this->status),
            'receipts' => $this->receipts
                ->sortBy('id')
                ->map(fn ($receipt): array => [
                    'id' => (int) $receipt->id,
                    'invoice_id' => (int) $receipt->invoice_id,
                    'type' => $receipt->type,
                    'amount' => (int) $receipt->amount,
                    'invoice_number' => $receipt->invoice_number,
                    'customer_id' => $receipt->customer_id,
                    'customer_name' => $receipt->customer_name,
                    'template_name' => $receipt->template_name,
                    'billed_invoice_id' => $receipt->billed_invoice_id,
                    'billed_at' => $receipt->billed_at?->toISOString(),
                ])
                ->values(),
            'events' => $this->events
                ->sortByDesc('id')
                ->map(fn ($event): array => [
                    'id' => (int) $event->id,
                    'type' => $event->type,
                    'message' => $event->message,
                    'user_id' => $event->user_id,
                    'created_at' => $event->created_at?->toISOString(),
                ])
                ->values(),
            'expenses' => $this->expenses
                ->sortByDesc('id')
                ->map(fn ($expense): array => [
                    'id' => (int) $expense->id,
                    'category' => $expense->category,
                    'amount' => (int) $expense->amount,
                    'note' => $expense->note,
                    'expense_date' => $expense->expense_date?->format('Y-m-d'),
                ])
                ->values(),
            'money' => $service->money($this->resource),
            'documents' => $this->getMedia('pod')->map(fn ($media): array => [
                'id' => (int) $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => (int) $media->size,
                'url' => "/api/v1/trips/{$this->id}/pod/{$media->id}",
                'is_image' => str_starts_with((string) $media->mime_type, 'image/'),
                'category' => $media->getCustomProperty('category'),
                'created_at' => $media->created_at?->toISOString(),
            ])->values(),
        ];
    }
}
