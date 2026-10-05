<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\TasksProjects\Models\Project;

/**
 * A project as the API renders it. Money stays in integer minor units and
 * `default_rate` is minor units per hour.
 *
 * @property-read Project $resource
 */
final class ProjectResource extends JsonResource
{
    /** @var array<string, mixed>|null */
    private ?array $totals = null;

    /**
     * Add the detail screen's totals, which the list deliberately leaves out.
     *
     * @param  array<string, mixed>  $totals
     */
    public function withTotals(array $totals): self
    {
        $this->totals = $totals;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $project = $this->resource;

        $data = [
            'id' => (int) $project->id,
            'company_id' => (int) $project->company_id,
            'customer_id' => $project->customer_id === null ? null : (int) $project->customer_id,
            'name' => $project->name,
            'identifier' => $project->identifier,
            'description' => $project->description,
            'colour' => $project->colour,
            'status' => $project->status,
            'currency_id' => $project->currency_id === null ? null : (int) $project->currency_id,
            'default_rate' => $project->default_rate === null ? null : (int) $project->default_rate,
            'budget_minutes' => $project->budget_minutes === null ? null : (int) $project->budget_minutes,
            'due_date' => $project->due_date?->toDateString(),
            'creator_id' => $project->creator_id === null ? null : (int) $project->creator_id,
            'is_internal' => $project->isInternal(),
            'created_at' => $project->created_at?->toIso8601String(),
            'updated_at' => $project->updated_at?->toIso8601String(),
        ];

        if ($this->totals !== null) {
            $data['totals'] = $this->totals;
        }

        return $data;
    }
}
