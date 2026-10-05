<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use InvalidArgumentException;
use Modules\TasksProjects\Application\BoardOrderingService;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;
use Modules\TasksProjects\Tests\TestCase;

final class BoardOrderingServiceTest extends TestCase
{
    private const COMPANY = 9;

    private BoardOrderingService $board;

    private TaskStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->board = new BoardOrderingService;
        $this->status = $this->makeStatus(self::COMPANY);
    }

    public function test_the_first_card_of_an_empty_column_takes_one_step(): void
    {
        self::assertSame('1024.0000000000', $this->board->positionFor(self::COMPANY, (int) $this->status->id));
    }

    public function test_appending_lands_one_step_past_the_last_card(): void
    {
        $this->cardAt('1024');
        $this->cardAt('2048');

        self::assertSame('3072.0000000000', $this->board->positionFor(self::COMPANY, (int) $this->status->id));
    }

    public function test_dropping_under_the_last_card_lands_one_step_past_it(): void
    {
        $last = $this->cardAt('2048');

        self::assertSame(
            '3072.0000000000',
            $this->board->positionFor(self::COMPANY, (int) $this->status->id, (int) $last->id),
        );
    }

    public function test_prepending_halves_the_first_cards_position(): void
    {
        $first = $this->cardAt('1024');

        self::assertSame(
            '512.0000000000',
            $this->board->positionFor(self::COMPANY, (int) $this->status->id, null, (int) $first->id),
        );
    }

    public function test_dropping_between_two_cards_takes_the_midpoint(): void
    {
        $above = $this->cardAt('1024');
        $below = $this->cardAt('2048');

        self::assertSame(
            '1536.0000000000',
            $this->board->positionFor(self::COMPANY, (int) $this->status->id, (int) $above->id, (int) $below->id),
        );
    }

    public function test_a_gap_too_small_to_halve_renormalises_the_column_first(): void
    {
        $above = $this->cardAt('1024.0000000000');
        $below = $this->cardAt('1024.0000005000');
        $tail = $this->cardAt('4096.0000000000');

        $position = $this->board->positionFor(self::COMPANY, (int) $this->status->id, (int) $above->id, (int) $below->id);

        self::assertSame('1536.0000000000', $position);
        self::assertSame('1024.0000000000', $above->fresh()->board_position);
        self::assertSame('2048.0000000000', $below->fresh()->board_position);
        self::assertSame('3072.0000000000', $tail->fresh()->board_position);
    }

    public function test_renormalise_rewrites_the_column_to_whole_steps_in_order(): void
    {
        $third = $this->cardAt('9000.0000000000');
        $first = $this->cardAt('12.5000000000');
        $second = $this->cardAt('900.0000000000');

        self::assertSame(3, $this->board->renormalise(self::COMPANY, (int) $this->status->id));

        self::assertSame('1024.0000000000', $first->fresh()->board_position);
        self::assertSame('2048.0000000000', $second->fresh()->board_position);
        self::assertSame('3072.0000000000', $third->fresh()->board_position);
    }

    public function test_it_refuses_a_neighbour_from_another_company(): void
    {
        $foreign = $this->cardAt('1024', 10);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Task {$foreign->id} does not belong to company 9.");

        $this->board->positionFor(self::COMPANY, (int) $this->status->id, (int) $foreign->id);
    }

    public function test_it_refuses_a_neighbour_from_another_column(): void
    {
        $other = $this->makeStatus(self::COMPANY, ['name' => 'Done', 'position' => 2, 'is_default' => false]);
        $card = $this->cardAt('1024');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Task {$card->id} is not in task status {$other->id}.");

        $this->board->positionFor(self::COMPANY, (int) $other->id, (int) $card->id);
    }

    private function cardAt(string $position, int $companyId = self::COMPANY): Task
    {
        return $this->makeTask($companyId, [
            'task_status_id' => $this->status->id,
            'board_position' => $position,
        ]);
    }
}
