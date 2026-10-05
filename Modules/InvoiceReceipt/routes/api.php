<?php

use Illuminate\Support\Facades\Route;

/*
 * Invoice Receipt module API routes (placeholder).
 * Invoice Receipts use the core Invoice APIs with template_name = 'invoice_receipt'.
 */
Route::prefix('api/v1/invoice-receipts')
    ->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        // API endpoints can be added here if needed in the future
    });
