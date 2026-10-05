<?php

use Illuminate\Support\Facades\Route;
use Modules\LrReceipt\Http\Controllers\CustomerLrReceiptsController;
use Modules\LrReceipt\Http\Controllers\LrReceiptAutoFillController;
use Modules\LrReceipt\Http\Controllers\LrReceiptController;

/*
 * LR Receipt module API routes.
 */
Route::prefix('api/v1/lr-receipts')
    ->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        Route::get('/', [LrReceiptController::class, 'index']);
        Route::get('/{id}', [LrReceiptController::class, 'show']);
        Route::post('/auto-fill', LrReceiptAutoFillController::class);
        Route::get('/{id}/lorry-receipt-status', [LrReceiptController::class, 'lorryReceiptStatus']);
    });

// Customer portal
Route::prefix('api/v1/customer/lr-receipts')
    ->middleware(['api', 'auth:customer'])
    ->group(function (): void {
        Route::get('/', [CustomerLrReceiptsController::class, 'index']);
        Route::get('/{id}', [CustomerLrReceiptsController::class, 'show']);
    });
