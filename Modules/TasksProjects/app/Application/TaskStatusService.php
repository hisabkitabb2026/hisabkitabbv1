<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\TasksProjects\Application\Exceptions\StatusInUse;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;

/**
 * The board columns of one company.
 *
 * The four defaults are created here rather than seeded by a migration: a
 * migration runs once per database while companies come and go, and the
 * reversible-migration contract keeps data writes out of `up()`.
 */
final class TaskStatusService
{
    /** @var list<array{name: string, colour: string, is_default: bool, is_closed: bool}> */
    public const DEFAULTS = [
        ['name' => 'Backlog', 'colour' => '#94a3b8', 'is_default' => true, 'is_closed' => false],
        ['name' => 'In Progress', 'colour' => '#3b82f6', 'is_default' => false, 'is_closed' => false],
        ['name' => 'Review', 'colour' => '#f59e0b', 'is_default' => false, 'is_closed' => false],
        ['name' => 'Done', 'colour' => '#22c55e', 'is_default' => false, 'is_closed' => true],
    ];

    /** Create Backlog / In Progress / Review / Done, but only for a company that has no statuses yet. */
    public function ensureDefaults(int $companyId): void
    {
        DB::transaction(function () use ($companyId): void {
            if (TaskStatus::query()->forCompany($companyId)->exists()) {
                return;
            }

            foreach (self::DEFAULTS as $position => $status) {
                TaskStatus::query()->create($status + [
                    'company_id' => $companyId,
                    'position' => $position + 1,
                ]);
            }
        });
    }

    /** @return Collection<int, TaskStatus> */
    public function listFor(int $companyId): Collection
    {
        return TaskStatus::query()
            ->forCompany($companyId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function findForCompany(int $companyId, int $id): TaskStatus
    {
        $status = TaskStatus::query()->forCompany($companyId)->find($id);

        if ($status === null) {
            throw (new ModelNotFoundException)->setModel(TaskStatus::class, [$id]);
        }

        return $status;
    }

    /** The status new tasks land in, creating the defaults when the company has none. */
    public function defaultFor(int $companyId): TaskStatus
    {
        $this->ensureDefaults($companyId);

        $status = TaskStatus::query()
            ->forCompany($companyId)
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($status === null) {
            throw (new ModelNotFoundException)->setModel(TaskStatus::class);
        }

        return $status;
    }

    /** @param array<string, mixed> $attributes */
    public function create(int $companyId, array $attributes): TaskStatus
    {
        return DB::transaction(function () use ($companyId, $attributes): TaskStatus {
            $status = TaskStatus::query()->create([
                'company_id' => $companyId,
                'name' => $attributes['name'],
                'colour' => $attributes['colour'] ?? null,
                'position' => (int) ($attributes['position'] ?? $this->nextPosition($companyId)),
                'is_default' => (bool) ($attributes['is_default'] ?? false),
                'is_closed' => (bool) ($attributes['is_closed'] ?? false),
            ]);

            $this->keepSingleDefault($companyId, $status);

            return $status;
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(int $companyId, int $id, array $attributes): TaskStatus
    {
        return DB::transaction(function () use ($companyId, $id, $attributes): TaskStatus {
            $status = $this->findForCompany($companyId, $id);

            foreach (['name', 'colour', 'position', 'is_default', 'is_closed'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $status->{$field} = $attributes[$field];
                }
            }

            $status->save();
            $this->keepSingleDefault($companyId, $status);

            return $status;
        });
    }

    /**
     * Apply the wanted order. Statuses the caller left out keep their relative
     * order and follow the listed ones.
     *
     * @param  list<int>  $ids
     */
    public function reorder(int $companyId, array $ids): void
    {
        DB::transaction(function () use ($companyId, $ids): void {
            $statuses = $this->listFor($companyId)->keyBy('id');
            $position = 0;

            foreach ($ids as $id) {
                $status = $statuses->get($id);

                if ($status === null) {
                    throw (new ModelNotFoundException)->setModel(TaskStatus::class, [$id]);
                }

                $status->position = ++$position;
                $status->save();
                $statuses->forget($id);
            }

            foreach ($statuses as $status) {
                $status->position = ++$position;
                $status->save();
            }
        });
    }

    public function delete(int $companyId, int $id): void
    {
        DB::transaction(function () use ($companyId, $id): void {
            $status = $this->findForCompany($companyId, $id);

            $tasks = Task::query()->forCompany($companyId)->where('task_status_id', $status->id)->count();
            if ($tasks > 0) {
                throw StatusInUse::hasTasks((int) $status->id, $tasks);
            }

            if (TaskStatus::query()->forCompany($companyId)->count() <= 1) {
                throw StatusInUse::isLast((int) $status->id);
            }

            $others = TaskStatus::query()
                ->forCompany($companyId)
                ->where('id', '!=', $status->id)
                ->where('is_default', true)
                ->exists();

            if ($status->is_default && ! $others) {
                throw StatusInUse::isDefault((int) $status->id);
            }

            $status->delete();
        });
    }

    private function nextPosition(int $companyId): int
    {
        return (int) TaskStatus::query()->forCompany($companyId)->max('position') + 1;
    }

    /** Exactly one status per company carries is_default. */
    private function keepSingleDefault(int $companyId, TaskStatus $status): void
    {
        if (! $status->is_default) {
            return;
        }

        TaskStatus::query()
            ->forCompany($companyId)
            ->where('id', '!=', $status->id)
            ->where('is_default', true)
            ->get()
            ->each(static function (TaskStatus $other): void {
                $other->is_default = false;
                $other->save();
            });
    }
}
