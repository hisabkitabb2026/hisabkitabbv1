<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\TasksProjects\Models\ProjectMember;

/**
 * A user attached to a project. `user_id` points at a host user without a
 * foreign key, so a member who has left the company still renders here.
 *
 * @property-read ProjectMember $resource
 */
final class ProjectMemberResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $member = $this->resource;

        return [
            'id' => (int) $member->id,
            'company_id' => (int) $member->company_id,
            'project_id' => (int) $member->project_id,
            'user_id' => (int) $member->user_id,
            'rate' => $member->rate === null ? null : (int) $member->rate,
            'created_at' => $member->created_at?->toIso8601String(),
            'updated_at' => $member->updated_at?->toIso8601String(),
        ];
    }
}
