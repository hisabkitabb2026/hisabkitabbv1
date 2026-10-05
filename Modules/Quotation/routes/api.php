<?php

use Illuminate\Support\Facades\Route;

/*
 * Quotation module API routes (placeholder).
 * Quotations use the core Estimate APIs with template_name = 'quotation'.
 */
Route::prefix('api/v1/quotations')
    ->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        // API endpoints can be added here if needed in the future
    });
