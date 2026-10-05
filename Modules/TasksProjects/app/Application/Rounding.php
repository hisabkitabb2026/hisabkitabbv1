<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use InvalidArgumentException;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Billing increments, applied when a time entry is saved rather than when it is
 * invoiced, so what the user sees on the timesheet is what gets billed.
 *
 * The direction is a company setting. `nearest` is the historic behaviour and
 * stays the default; `up` and `down` are the two ways a firm that bills in
 * blocks wants the block chosen, and neither reads as "nearest" to a client.
 */
final class Rounding
{
    public const NEAREST = 'nearest';

    public const UP = 'up';

    public const DOWN = 'down';

    /** @var list<string> */
    public const DIRECTIONS = [self::NEAREST, self::UP, self::DOWN];

    /**
     * Round a duration to a multiple of the increment.
     *
     * Zero stays zero whichever way the company rounds, because an entry with
     * no time is not worth an increment. Rounding `nearest` then treats a spell
     * shorter than one increment as a whole one: a two minute call on a fifteen
     * minute increment bills a quarter of an hour, never nothing. Rounding
     * `down` is the one direction that may answer zero for real work, which is
     * exactly what a firm asking to round down is asking for.
     */
    public static function roundMinutes(int $minutes, int $increment, string $direction = self::NEAREST): int
    {
        if (! in_array($increment, ModuleSettings::ROUNDING_INCREMENTS, true)) {
            throw new InvalidArgumentException(
                "Rounding increment {$increment} is not one of ".implode(', ', ModuleSettings::ROUNDING_INCREMENTS).'.',
            );
        }

        if (! in_array($direction, self::DIRECTIONS, true)) {
            throw new InvalidArgumentException(
                "Rounding direction {$direction} is not one of ".implode(', ', self::DIRECTIONS).'.',
            );
        }

        if ($minutes <= 0) {
            return 0;
        }

        return match ($direction) {
            self::UP => (int) ceil($minutes / $increment) * $increment,
            self::DOWN => intdiv($minutes, $increment) * $increment,
            default => $minutes < $increment
                ? $increment
                : (int) round($minutes / $increment) * $increment,
        };
    }
}
