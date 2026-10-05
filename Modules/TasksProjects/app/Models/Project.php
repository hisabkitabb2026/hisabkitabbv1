<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A body of work for one customer, or an internal project when `customer_id`
 * is null. Money columns hold integer minor units; `default_rate` is minor
 * units per hour.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $customer_id
 * @property string $status
 * @property int|null $currency_id
 * @property int|null $default_rate
 */
class Project extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_ARCHIVED = 'ARCHIVED';

    protected $table = 'tp_projects';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'customer_id' => 'integer',
            'currency_id' => 'integer',
            'default_rate' => 'integer',
            'budget_minutes' => 'integer',
            'creator_id' => 'integer',
            'due_date' => 'date',
        ];
    }

    /** @return HasMany<ProjectMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /** @return HasMany<TimeEntry, $this> */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'project_id');
    }

    public function isInternal(): bool
    {
        return $this->customer_id === null;
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
