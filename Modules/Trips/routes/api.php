<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Trips\Http\Controllers\TripExpensesController;
use Modules\Trips\Http\Controllers\TripReceiptsController;
use Modules\Trips\Http\Controllers\TripReportsController;
use Modules\Trips\Http\Controllers\TripsController;
use Modules\Trips\Http\Controllers\TripStatusesController;

/*
 * The Trips module's HTTP surface.
 *
 * Every route sits behind the host stack: `company` puts the active company on
 * the request as the `company` header, `bouncer` scopes permissions to it, and
 * each action then checks its own module ability. The slug prefix means a
 * future core route can never collide with one of these.
 */
Route::prefix('api/v1/trips')->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])->group(function (): void {
    Route::get('/', [TripsController::class, 'index'])->name('trips.index');
    Route::post('/', [TripsController::class, 'store'])->name('trips.store');
    Route::post('/from-lrs', [TripsController::class, 'createFromLrs'])->name('trips.from-lrs');
    Route::get('/statuses', [TripStatusesController::class, 'index'])->name('trips.statuses.index');
    Route::get('/unlinked-lrs', [TripReceiptsController::class, 'unlinkedLrs'])->name('trips.unlinked-lrs');
    Route::post('/lorry-match', [TripReceiptsController::class, 'lorryMatchPreview'])->name('trips.lorry-match');

    Route::get('/{id}', [TripsController::class, 'show'])->name('trips.show');
    Route::put('/{id}', [TripsController::class, 'update'])->name('trips.update');
    Route::delete('/{id}', [TripsController::class, 'destroy'])->name('trips.destroy');
    Route::post('/{id}/move', [TripsController::class, 'move'])->name('trips.move');
    Route::patch('/{id}/status', [TripsController::class, 'changeStatus'])->name('trips.status');
    Route::post('/{id}/cancel', [TripsController::class, 'cancel'])->name('trips.cancel');

    Route::post('/{id}/receipts', [TripReceiptsController::class, 'store'])->name('trips.receipts.store');
    Route::delete('/{id}/receipts/{receiptId}', [TripReceiptsController::class, 'destroy'])->name('trips.receipts.destroy');

    Route::post('/{id}/fetch-invoice-receipt', [TripReceiptsController::class, 'fetchInvoiceReceipts'])->name('trips.fetch-invoice-receipt');
    Route::post('/{id}/pod', [TripReceiptsController::class, 'uploadPod'])->name('trips.pod.store');
    Route::get('/{id}/pod/{mediaId}', [TripReceiptsController::class, 'showDocument'])->name('trips.pod.show');
    Route::delete('/{id}/pod/{mediaId}', [TripReceiptsController::class, 'destroyPod'])->name('trips.pod.destroy');

    Route::post('/{id}/expenses', [TripExpensesController::class, 'store'])->name('trips.expenses.store');
    Route::delete('/{id}/expenses/{expenseId}', [TripExpensesController::class, 'destroy'])->name('trips.expenses.destroy');

    Route::get('/reports/summary', [TripReportsController::class, 'summary'])->name('trips.reports.summary');
    Route::get('/reports/pdf', [TripReportsController::class, 'pdf'])->name('trips.reports.pdf');
});
