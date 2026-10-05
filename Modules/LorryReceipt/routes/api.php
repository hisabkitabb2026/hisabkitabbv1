<?php

use Illuminate\Support\Facades\Route;
use Modules\LorryReceipt\Http\Controllers\BankController;
use Modules\LorryReceipt\Http\Controllers\CustomerLorryReceiptsController;
use Modules\LorryReceipt\Http\Controllers\LorryPartyProfileController;
use Modules\LorryReceipt\Http\Controllers\LorryReceiptController;

/*
 * Lorry Receipt module API routes.
 */
Route::prefix('api/v1/lorry-receipts')
    ->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        Route::get('/', [LorryReceiptController::class, 'index']);

        // Banks (server-side storage) — must be before /{id} to avoid route conflict
        Route::get('/banks', [BankController::class, 'index']);
        Route::post('/banks', [BankController::class, 'store']);
        Route::delete('/banks/{bank}', [BankController::class, 'destroy']);

        // Party Profiles (full CRUD)
        Route::get('/lorry-party-profiles', [LorryPartyProfileController::class, 'index']);
        Route::post('/lorry-party-profiles', [LorryPartyProfileController::class, 'store']);
        Route::get('/lorry-party-profiles/{id}', [LorryPartyProfileController::class, 'show']);
        Route::put('/lorry-party-profiles/{id}', [LorryPartyProfileController::class, 'update']);
        Route::delete('/lorry-party-profiles/{id}', [LorryPartyProfileController::class, 'destroy']);

        Route::get('/{id}', [LorryReceiptController::class, 'show'])->whereNumber('id');
    });

// Customer portal
Route::prefix('api/v1/customer/lorry-receipts')
    ->middleware(['api', 'auth:customer'])
    ->group(function (): void {
        Route::get('/', [CustomerLorryReceiptsController::class, 'index']);
        Route::get('/{id}', [CustomerLorryReceiptsController::class, 'show']);
    });
