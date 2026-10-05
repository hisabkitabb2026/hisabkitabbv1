<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Modules\TasksProjects\Application\BoardQuery;
use Modules\TasksProjects\Application\TaskStatusService;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;
use Modules\TasksProjects\Tests\TestCase;

final class BoardQueryTest extends TestCase
{
    private const COMPANY = 9;

    public function test_it_returns_every_column_in_order_with_its_cards_in_board_order(): void
    {
        $statuses = $this->board();
        $second = $this->card('Second', (int) $statuses['Backlog']->id, '2048');
        $first = $this->card('First', (int) $statuses['Backlog']->id, '1024');
        $done = $this->card('Done card', (int) $statuses['Done']->id, '1024');

        $columns = (new BoardQuery)->columns(self::COMPANY);

        self::assertSame(
            ['Backlog', 'In Progress', 'Review', 'Done'],
            array_map(static fn (array $column): string => $column['status']->name, $columns),
        );
        self::assertSame([(int) $first->id, (int) $second->id], array_map(
            static fn (Task $task): int => (int) $task->id,
            $columns[0]['tasks'],
        ));
        self::assertSame([], $columns[1]['tasks']);
        self::assertSame([(int) $done->id], array_map(static fn (Task $task): int => (int) $task->id, $columns[3]['tasks']));
    }

    public function test_it_filters_by_project_and_assignee_and_never_leaves_the_company(): void
    {
        $statuses = $this->board();
        $backlog = (int) $statuses['Backlog']->id;
        $project = $this->makeProject(self::COMPANY);
        $mine = $this->card('Mine', $backlog, '1024', ['project_id' => $project->id, 'assignee_id' => 7]);
        $this->card('Theirs', $backlog, '2048', ['project_id' => $project->id, 'assignee_id' => 8]);
        $this->card('Other project', $backlog, '3072', ['assignee_id' => 7]);
        $this->makeTask(10, ['name' => 'Another company']);

        $columns = (new BoardQuery)->columns(self::COMPANY, (int) $project->id, 7);

        self::assertSame([(int) $mine->id], array_map(static fn (Task $task): int => (int) $task->id, $columns[0]['tasks']));
    }

    /** @return array<string, TaskStatus> */
    private function board(): array
    {
        $statuses = new TaskStatusService;
        $statuses->ensureDefaults(self::COMPANY);

        return $statuses->listFor(self::COMPANY)->keyBy('name')->all();
    }

    /** @param array<string, mixed> $attributes */
    private function card(string $name, int $statusId, string $position, array $attributes = []): Task
    {
        return $this->makeTask(self::COMPANY, $attributes + [
            'name' => $name,
            'task_status_id' => $statusId,
            'board_position' => $position,
        ]);
    }
}
