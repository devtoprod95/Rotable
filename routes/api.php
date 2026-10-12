<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\EncodeController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::any('/', [ApiController::class, 'log'])->name('');
    Route::post('/encode/sojaeCallback', [EncodeController::class, 'sojaeCallback']);
});
