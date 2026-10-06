<?php

use Illuminate\Support\Facades\Route;
use Modules\InvoiceReceipt\Http\Controllers\ConsignmentLookupController;

/*
 * Invoice Receipt module API routes.
 */
Route::prefix('api/v1/invoice-receipts')
    ->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        Route::get('/consignments/search', [ConsignmentLookupController::class, 'search']);
        Route::get('/consignments/{number}', [ConsignmentLookupController::class, 'show']);
    });

