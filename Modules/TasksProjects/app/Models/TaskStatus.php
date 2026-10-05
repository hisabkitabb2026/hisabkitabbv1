<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One column of the board, per company. `is_default` decides where new tasks
 * land and `is_closed` decides what counts as done for reporting.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property int $position
 * @property bool $is_default
 * @property bool $is_closed
 */
class TaskStatus extends Model
{
    protected $table = 'tp_task_statuses';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'position' => 'integer',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'task_status_id');
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
