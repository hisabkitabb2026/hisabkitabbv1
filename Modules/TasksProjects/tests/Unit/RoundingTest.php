<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use InvalidArgumentException;
use Modules\TasksProjects\Application\Rounding;
use Modules\TasksProjects\Tests\TestCase;

final class RoundingTest extends TestCase
{
    public function test_a_one_minute_increment_leaves_every_duration_alone(): void
    {
        self::assertSame(0, Rounding::roundMinutes(0, 1));
        self::assertSame(1, Rounding::roundMinutes(1, 1));
        self::assertSame(137, Rounding::roundMinutes(137, 1));
    }

    public function test_it_rounds_to_the_nearest_multiple_of_the_increment(): void
    {
        self::assertSame(15, Rounding::roundMinutes(22, 15));
        self::assertSame(30, Rounding::roundMinutes(23, 15));
        self::assertSame(30, Rounding::roundMinutes(30, 15));
        self::assertSame(6, Rounding::roundMinutes(8, 6));
        self::assertSame(12, Rounding::roundMinutes(9, 6));
        self::assertSame(30, Rounding::roundMinutes(44, 30));
        self::assertSame(60, Rounding::roundMinutes(45, 30));
    }

    public function test_nothing_logged_stays_nothing_billed(): void
    {
        self::assertSame(0, Rounding::roundMinutes(0, 30));
        self::assertSame(0, Rounding::roundMinutes(-5, 15));
    }

    public function test_anything_above_zero_bills_at_least_one_increment(): void
    {
        self::assertSame(15, Rounding::roundMinutes(1, 15));
        self::assertSame(15, Rounding::roundMinutes(7, 15));
        self::assertSame(6, Rounding::roundMinutes(1, 6));
        self::assertSame(30, Rounding::roundMinutes(2, 30));
    }

    public function test_it_refuses_an_increment_the_settings_do_not_offer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rounding increment 7 is not one of 1, 5, 6, 15, 30, 60.');

        Rounding::roundMinutes(10, 7);
    }

    public function test_rounding_up_takes_the_whole_increment_every_time(): void
    {
        self::assertSame(15, Rounding::roundMinutes(1, 15, Rounding::UP));
        self::assertSame(15, Rounding::roundMinutes(15, 15, Rounding::UP));
        self::assertSame(30, Rounding::roundMinutes(16, 15, Rounding::UP));
        self::assertSame(60, Rounding::roundMinutes(46, 15, Rounding::UP));
        self::assertSame(137, Rounding::roundMinutes(137, 1, Rounding::UP));
    }

    public function test_rounding_down_drops_the_part_increment_and_may_bill_nothing(): void
    {
        self::assertSame(0, Rounding::roundMinutes(14, 15, Rounding::DOWN));
        self::assertSame(15, Rounding::roundMinutes(15, 15, Rounding::DOWN));
        self::assertSame(15, Rounding::roundMinutes(29, 15, Rounding::DOWN));
        self::assertSame(120, Rounding::roundMinutes(137, 60, Rounding::DOWN));
        self::assertSame(137, Rounding::roundMinutes(137, 1, Rounding::DOWN));
    }

    public function test_nothing_logged_stays_nothing_billed_whichever_way_it_rounds(): void
    {
        foreach (Rounding::DIRECTIONS as $direction) {
            self::assertSame(0, Rounding::roundMinutes(0, 30, $direction));
            self::assertSame(0, Rounding::roundMinutes(-5, 30, $direction));
        }
    }

    public function test_it_refuses_a_direction_it_does_not_know(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rounding direction sideways is not one of nearest, up, down.');

        Rounding::roundMinutes(10, 15, 'sideways');
    }
}
