<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A unit of work on the board. `customer_id` is denormalised from the project,
 * or set directly for a standalone billable task, and is what the billing
 * screen groups by. `board_position` is a decimal kept as a string so PHP
 * float formatting never leaks into the stored value.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $project_id
 * @property int|null $customer_id
 * @property int $task_status_id
 * @property int $number
 * @property string $name
 * @property int|null $assignee_id
 * @property bool $billable
 * @property int|null $rate
 * @property string $board_position
 * @property Carbon|null $closed_at
 */
class Task extends Model
{
    public const PRIORITY_LOW = 'LOW';

    public const PRIORITY_NORMAL = 'NORMAL';

    public const PRIORITY_HIGH = 'HIGH';

    public const PRIORITY_URGENT = 'URGENT';

    /** @var list<string> */
    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    /**
     * The time block the API renders, hung on the model by TaskTimeSummary.
     *
     * A real property rather than an attribute: it is derived from the time
     * entries, never a column, and must never travel back into a save().
     *
     * @var array<string, mixed>|null
     */
    public ?array $timeSummary = null;

    protected $table = 'tp_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'project_id' => 'integer',
            'customer_id' => 'integer',
            'task_status_id' => 'integer',
            'number' => 'integer',
            'assignee_id' => 'integer',
            'estimated_minutes' => 'integer',
            'billable' => 'boolean',
            'rate' => 'integer',
            'board_position' => 'decimal:10',
            'creator_id' => 'integer',
            'due_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<TaskStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    /** @return HasMany<TimeEntry, $this> */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'task_id');
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
