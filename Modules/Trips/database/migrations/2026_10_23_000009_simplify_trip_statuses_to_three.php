<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripStatus;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $companyIds = TripStatus::query()->distinct()->pluck('company_id');

            foreach ($companyIds as $companyId) {
                // Ensure 'in_transit' and 'delivered' exist
                $inTransit = TripStatus::query()
                    ->where('company_id', $companyId)
                    ->where('code', 'in_transit')
                    ->first();

                $delivered = TripStatus::query()
                    ->where('company_id', $companyId)
                    ->where('code', 'delivered')
                    ->first();

                // If any trips are in 'assigned', reassign them to 'in_transit'
                $assigned = TripStatus::query()
                    ->where('company_id', $companyId)
                    ->where('code', 'assigned')
                    ->first();

                if ($assigned) {
                    $target = $inTransit ?? $delivered;
                    if ($target) {
                        Trip::query()
                            ->where('company_id', $companyId)
                            ->where('status_id', $assigned->id)
                            ->update(['status_id' => $target->id]);
                    }

                    $assigned->delete();
                }

                // Ensure the 3 canonical statuses exist with proper positions and colours
                $canonical = [
                    ['code' => 'booked', 'name' => 'Booked', 'colour' => '#6366f1', 'position' => 1, 'is_default' => true, 'is_closed' => false],
                    ['code' => 'in_transit', 'name' => 'In Transit', 'colour' => '#3b82f6', 'position' => 2, 'is_default' => false, 'is_closed' => false],
                    ['code' => 'delivered', 'name' => 'Delivered', 'colour' => '#10b981', 'position' => 3, 'is_default' => false, 'is_closed' => true],
                ];

                foreach ($canonical as $target) {
                    TripStatus::query()->updateOrCreate(
                        ['company_id' => $companyId, 'code' => $target['code']],
                        $target
                    );
                }
            }
        });
    }

    public function down(): void
    {
        // No-op
    }
};
