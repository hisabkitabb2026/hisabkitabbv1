<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Modules\Trips\Application\TripStatusService;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripStatus;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== SYNC TRIP STATUSES (3 STATUSES: Booked -> In Transit -> Delivered) ===\n";

$companyIds = TripStatus::query()->distinct()->pluck('company_id');
if ($companyIds->isEmpty()) {
    $companyIds = collect([1]);
}

foreach ($companyIds as $companyId) {
    echo "\nProcessing Company ID: {$companyId}...\n";

    /** @var TripStatusService $statusService */
    $statusService = app(TripStatusService::class);
    $statusService->ensureDefaults($companyId);

    $statuses = TripStatus::query()->forCompany($companyId)->orderBy('position')->get();
    echo "Current Active Statuses ({$statuses->count()}):\n";
    foreach ($statuses as $s) {
        $tripCount = Trip::query()->where('company_id', $companyId)->where('status_id', $s->id)->count();
        echo "  - [Position {$s->position}] {$s->name} (code: '{$s->code}', colour: '{$s->colour}') => {$tripCount} trips\n";
    }
}

echo "\n=== SYNC COMPLETED SUCCESSFULLY ===\n";
