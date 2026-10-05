<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools\Concerns;

use Carbon\Carbon;

trait ResolvesPeriod
{
    protected const ALL_PERIODS = ['all_time', 'today', 'this_week', 'this_month', 'last_month', 'this_quarter', 'this_year', 'last_year'];

    /** @return array{0: Carbon, 1: Carbon}|null */
    protected function rangeFor(string $period): ?array
    {
        $now = Carbon::now();

        return match ($period) {
            'all_time' => null,
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [$now->copy()->subYearNoOverflow()->startOfYear(), $now->copy()->subYearNoOverflow()->endOfYear()],
            default => null,
        };
    }
}
