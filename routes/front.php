<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnlineOrdersController;
use App\Http\Controllers\Api\FrontProductCardsController;
use App\Http\Controllers\Api\FrontWebsiteSettingsController;

Route::prefix('front')->group(function () {
    Route::get('products',     [FrontProductCardsController::class,    'index']);
    Route::get('settings',     [FrontWebsiteSettingsController::class, 'show']);

    Route::middleware(['auth.customer'])->group(function () {
        Route::post('orders',  [OnlineOrdersController::class, 'store']);
        Route::get('orders',   [OnlineOrdersController::class, 'index']);
    });
});
