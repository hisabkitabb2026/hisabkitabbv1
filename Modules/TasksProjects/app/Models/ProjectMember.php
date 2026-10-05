<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A host user attached to a project, with an optional rate that beats the
 * project default for that member's own time.
 *
 * `user_id` points at the host `users` table without a database foreign key:
 * removing a member detaches the row here and leaves their time entries intact.
 *
 * @property int $id
 * @property int $company_id
 * @property int $project_id
 * @property int $user_id
 * @property int|null $rate
 */
class ProjectMember extends Model
{
    protected $table = 'tp_project_members';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'project_id' => 'integer',
            'user_id' => 'integer',
            'rate' => 'integer',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where($this->getTable().'.company_id', $companyId);
    }
}
