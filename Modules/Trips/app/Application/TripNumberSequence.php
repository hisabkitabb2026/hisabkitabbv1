<?php

declare(strict_types=1);

namespace Modules\Trips\Application;

use Illuminate\Support\Facades\DB;
use Modules\Trips\Models\Trip;

/**
 * Trip numbers, one series per company: 1, 2, 3, ...
 */
final class TripNumberSequence
{
    public function next(int $companyId): int
    {
        return DB::transaction(function () use ($companyId): int {
            $latest = Trip::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->max('trip_no');

            return (int) $latest + 1;
        });
    }
}
