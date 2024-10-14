<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OnlineOrdersController;
use App\Http\Controllers\Api\FrontProductCardsController;
use App\Http\Controllers\Api\FrontWebsiteSettingsController;
use App\Http\Controllers\Api\Front\CartController;

Route::prefix('front')->group(function(){
    Route::get('products',[FrontProductCardsController::class,'index']);
    Route::get('settings',[FrontWebsiteSettingsController::class,'show']);
    Route::post('cart/products',[CartController::class,'getProducts']);
    Route::post('cart/check-stock',[CartController::class,'checkStock']);
    Route::middleware(['auth.customer'])->group(function(){
        Route::post('orders',[OnlineOrdersController::class,'store']);
        Route::get('orders', [OnlineOrdersController::class,'index']);
    });
});
