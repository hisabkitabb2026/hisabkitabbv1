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
                // Ensure 'delivered' status exists
                $delivered = TripStatus::query()
                    ->where('company_id', $companyId)
                    ->where('code', 'delivered')
                    ->first();

                if (! $delivered) {
                    $delivered = TripStatus::query()->create([
                        'company_id' => $companyId,
                        'code' => 'delivered',
                        'name' => 'Delivered',
                        'colour' => '#10b981',
                        'position' => 4,
                        'is_default' => false,
                        'is_closed' => true,
                    ]);
                }

                // If any trips were in billed, settled, or cancelled statuses, reassign to delivered
                $obsolete = TripStatus::query()
                    ->where('company_id', $companyId)
                    ->whereIn('code', ['billed', 'settled', 'cancelled'])
                    ->get();

                foreach ($obsolete as $oldStatus) {
                    Trip::query()
                        ->where('company_id', $companyId)
                        ->where('status_id', $oldStatus->id)
                        ->update(['status_id' => $delivered->id]);

                    $oldStatus->delete();
                }

                // Ensure the 4 canonical statuses exist with proper positions and colours
                $canonical = [
                    ['code' => 'booked', 'name' => 'Booked', 'colour' => '#6366f1', 'position' => 1, 'is_default' => true, 'is_closed' => false],
                    ['code' => 'assigned', 'name' => 'Assigned', 'colour' => '#f59e0b', 'position' => 2, 'is_default' => false, 'is_closed' => false],
                    ['code' => 'in_transit', 'name' => 'In Transit', 'colour' => '#3b82f6', 'position' => 3, 'is_default' => false, 'is_closed' => false],
                    ['code' => 'delivered', 'name' => 'Delivered', 'colour' => '#10b981', 'position' => 4, 'is_default' => false, 'is_closed' => true],
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
