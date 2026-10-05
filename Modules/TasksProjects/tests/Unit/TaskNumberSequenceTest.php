<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Modules\TasksProjects\Application\TaskNumberSequence;
use Modules\TasksProjects\Tests\TestCase;

final class TaskNumberSequenceTest extends TestCase
{
    public function test_the_first_task_of_a_company_is_number_one(): void
    {
        self::assertSame(1, (new TaskNumberSequence)->next(9));
    }

    public function test_it_continues_from_the_highest_number_the_company_has_used(): void
    {
        $this->makeTask(9, ['number' => 1]);
        $this->makeTask(9, ['number' => 7]);

        self::assertSame(8, (new TaskNumberSequence)->next(9));
    }

    public function test_each_company_numbers_its_own_tasks(): void
    {
        $this->makeTask(9, ['number' => 41]);

        $sequence = new TaskNumberSequence;

        self::assertSame(42, $sequence->next(9));
        self::assertSame(1, $sequence->next(10));
    }
}
