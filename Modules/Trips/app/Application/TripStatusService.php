<?php

declare(strict_types=1);

namespace Modules\Trips\Application;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Trips\Models\TripStatus;

/**
 * The board's columns. The seven defaults are created lazily per company, the
 * first time any list is asked for, so enabling the module is enough.
 */
final class TripStatusService
{
    /**
     * code, name, colour, is_default, is_closed. The codes are the module's
     * machine names: the settle flow keys on them, so a renamed
     * status still behaves.
     *
     * @var array<int, array{code: string, name: string, colour: string, is_default: bool, is_closed: bool}>
     */
    public const DEFAULTS = [
        ['code' => 'booked', 'name' => 'Booked', 'colour' => '#6366f1', 'is_default' => true, 'is_closed' => false],
        ['code' => 'assigned', 'name' => 'Assigned', 'colour' => '#f59e0b', 'is_default' => false, 'is_closed' => false],
        ['code' => 'in_transit', 'name' => 'In Transit', 'colour' => '#3b82f6', 'is_default' => false, 'is_closed' => false],
        ['code' => 'delivered', 'name' => 'Delivered', 'colour' => '#10b981', 'is_default' => false, 'is_closed' => false],
        ['code' => 'billed', 'name' => 'Billed', 'colour' => '#8b5cf6', 'is_default' => false, 'is_closed' => false],
        ['code' => 'settled', 'name' => 'Settled', 'colour' => '#64748b', 'is_default' => false, 'is_closed' => true],
        ['code' => 'cancelled', 'name' => 'Cancelled', 'colour' => '#ef4444', 'is_default' => false, 'is_closed' => true],
    ];

    public function ensureDefaults(int $companyId): void
    {
        DB::transaction(function () use ($companyId): void {
            if (TripStatus::query()->forCompany($companyId)->exists()) {
                return;
            }

            foreach (self::DEFAULTS as $position => $status) {
                TripStatus::query()->create($status + [
                    'company_id' => $companyId,
                    'position' => $position + 1,
                ]);
            }
        });
    }

    /** @return Collection<int, TripStatus> */
    public function listFor(int $companyId): Collection
    {
        $this->ensureDefaults($companyId);

        return TripStatus::query()
            ->forCompany($companyId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /** The status new trips land in, creating the defaults when the company has none. */
    public function defaultFor(int $companyId): TripStatus
    {
        $this->ensureDefaults($companyId);

        $status = TripStatus::query()
            ->forCompany($companyId)
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($status === null) {
            throw new \RuntimeException('The trips module has no default status.');
        }

        return $status;
    }

    public function findForCompany(int $companyId, int $id): TripStatus
    {
        $status = TripStatus::query()->forCompany($companyId)->find($id);

        if ($status === null) {
            throw (new ModelNotFoundException)->setModel(TripStatus::class, [$id]);
        }

        return $status;
    }

    public function findByCode(int $companyId, string $code): ?TripStatus
    {
        return TripStatus::query()->forCompany($companyId)->where('code', $code)->first();
    }
}
