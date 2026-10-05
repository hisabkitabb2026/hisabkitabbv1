<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Work logged against a task, typed by hand or produced by the timer.
 *
 * `duration_minutes` is authoritative and already rounded to the company's
 * billing increment; `rate` and `amount` are frozen at save time so a later
 * rate change never rewrites history. `running_user_id` equals `user_id` while
 * a timer runs and is null once stopped, which is what the unique index on
 * `(company_id, running_user_id)` uses to keep one running timer per user.
 *
 * @property int $id
 * @property int $company_id
 * @property int $task_id
 * @property int|null $project_id
 * @property int $user_id
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property int $duration_minutes
 * @property bool $billable
 * @property int $rate
 * @property int $amount
 * @property int|null $currency_id
 * @property int|null $running_user_id
 * @property int|null $invoice_id
 * @property int|null $invoice_item_id
 * @property Carbon|null $invoiced_at
 */
class TimeEntry extends Model
{
    protected $table = 'tp_time_entries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'task_id' => 'integer',
            'project_id' => 'integer',
            'user_id' => 'integer',
            'duration_minutes' => 'integer',
            'billable' => 'boolean',
            'rate' => 'integer',
            'amount' => 'integer',
            'currency_id' => 'integer',
            'running_user_id' => 'integer',
            'invoice_id' => 'integer',
            'invoice_item_id' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'invoiced_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function isRunning(): bool
    {
        return $this->running_user_id !== null;
    }

    public function isStamped(): bool
    {
        return $this->invoice_id !== null;
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
