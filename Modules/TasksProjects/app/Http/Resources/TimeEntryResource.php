<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\TasksProjects\Models\TimeEntry;

/**
 * Logged time. Durations are minutes, `rate` is minor units per hour and
 * `amount` is the frozen money on the entry, also in minor units.
 *
 * @property-read TimeEntry $resource
 */
final class TimeEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $entry = $this->resource;

        return [
            'id' => (int) $entry->id,
            'company_id' => (int) $entry->company_id,
            'task_id' => (int) $entry->task_id,
            'project_id' => $entry->project_id === null ? null : (int) $entry->project_id,
            'user_id' => (int) $entry->user_id,
            'started_at' => $entry->started_at?->toIso8601String(),
            'ended_at' => $entry->ended_at?->toIso8601String(),
            'duration_minutes' => (int) $entry->duration_minutes,
            'description' => $entry->description,
            'billable' => (bool) $entry->billable,
            'rate' => (int) $entry->rate,
            'amount' => (int) $entry->amount,
            'currency_id' => $entry->currency_id === null ? null : (int) $entry->currency_id,
            'is_running' => $entry->isRunning(),
            'invoice_id' => $entry->invoice_id === null ? null : (int) $entry->invoice_id,
            'invoice_item_id' => $entry->invoice_item_id === null ? null : (int) $entry->invoice_item_id,
            'invoiced_at' => $entry->invoiced_at?->toIso8601String(),
            'created_at' => $entry->created_at?->toIso8601String(),
            'updated_at' => $entry->updated_at?->toIso8601String(),
        ];
    }
}
