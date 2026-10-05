<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\TasksProjects\Models\Task;

/**
 * Fractional ordering for the board, so a drag rewrites a single row.
 *
 * A task dropped between two neighbours takes the midpoint of their positions.
 * Halving a gap forever would eventually exhaust `decimal(20,10)`, so when the
 * gap falls below MIN_GAP the column is renormalised to whole steps first.
 */
final class BoardOrderingService
{
    /** Distance between two freshly numbered neighbours. */
    public const STEP = 1024.0;

    /** Below this the midpoint stops being representable, so the column is rewritten. */
    public const MIN_GAP = 0.000001;

    /**
     * The position a task should take between two neighbours of the same column.
     *
     * Both neighbours null appends: 1024 in an empty column, last + 1024
     * otherwise. Only `$afterTaskId` prepends to half of that task's position.
     * Only `$beforeTaskId` appends a step past it.
     */
    public function positionFor(int $companyId, int $statusId, ?int $beforeTaskId = null, ?int $afterTaskId = null): string
    {
        $before = $this->neighbourPosition($companyId, $statusId, $beforeTaskId);
        $after = $this->neighbourPosition($companyId, $statusId, $afterTaskId);

        if ($after !== null && ($after - ($before ?? 0.0)) < self::MIN_GAP) {
            $this->renormalise($companyId, $statusId);

            $before = $this->neighbourPosition($companyId, $statusId, $beforeTaskId);
            $after = $this->neighbourPosition($companyId, $statusId, $afterTaskId);
        }

        return match (true) {
            $before !== null && $after !== null => self::format(($before + $after) / 2),
            $before !== null => self::format($before + self::STEP),
            $after !== null => self::format($after / 2),
            default => self::format($this->lastPosition($companyId, $statusId) + self::STEP),
        };
    }

    /**
     * Rewrite a column to whole steps (1024, 2048, ...), keeping the current order.
     *
     * @return int the number of tasks renumbered
     */
    public function renormalise(int $companyId, int $statusId): int
    {
        return DB::transaction(static function () use ($companyId, $statusId): int {
            $tasks = Task::query()
                ->forCompany($companyId)
                ->where('task_status_id', $statusId)
                ->orderBy('board_position')
                ->orderBy('id')
                ->get();

            foreach ($tasks as $index => $task) {
                $task->board_position = self::format(($index + 1) * self::STEP);
                $task->save();
            }

            return $tasks->count();
        });
    }

    /** @throws InvalidArgumentException when the neighbour is outside the company or the column */
    private function neighbourPosition(int $companyId, int $statusId, ?int $taskId): ?float
    {
        if ($taskId === null) {
            return null;
        }

        $task = Task::query()->forCompany($companyId)->find($taskId);

        if ($task === null) {
            throw new InvalidArgumentException("Task {$taskId} does not belong to company {$companyId}.");
        }

        if ((int) $task->task_status_id !== $statusId) {
            throw new InvalidArgumentException("Task {$taskId} is not in task status {$statusId}.");
        }

        return (float) $task->board_position;
    }

    private function lastPosition(int $companyId, int $statusId): float
    {
        return (float) Task::query()
            ->forCompany($companyId)
            ->where('task_status_id', $statusId)
            ->max('board_position');
    }

    private static function format(float $position): string
    {
        return sprintf('%.10F', $position);
    }
}
