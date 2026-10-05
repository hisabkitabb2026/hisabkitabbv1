<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\TasksProjects\Models\TaskStatus;

/**
 * One column of the board.
 *
 * @property-read TaskStatus $resource
 */
final class TaskStatusResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $status = $this->resource;

        return [
            'id' => (int) $status->id,
            'company_id' => (int) $status->company_id,
            'name' => $status->name,
            'colour' => $status->colour,
            'position' => (int) $status->position,
            'is_default' => (bool) $status->is_default,
            'is_closed' => (bool) $status->is_closed,
            'created_at' => $status->created_at?->toIso8601String(),
            'updated_at' => $status->updated_at?->toIso8601String(),
        ];
    }
}
