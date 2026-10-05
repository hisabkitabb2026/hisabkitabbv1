<?php

use Illuminate\Support\Facades\Route;
use Modules\AccessRequest\Http\Controllers\AccessRequestController;

Route::prefix('api/v1/access-request')
    ->middleware(['api', 'auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        Route::post('/', AccessRequestController::class);
    });
