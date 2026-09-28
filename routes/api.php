<?php

use App\Http\Controllers\Api\OneCExchangeController;
use Illuminate\Support\Facades\Route;

Route::post('/1c/exchange', OneCExchangeController::class)
    ->middleware('onec.auth')
    ->name('api.1c.exchange');
