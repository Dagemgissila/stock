<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.api'])->group(function () {
    Route::get('notifications',         fn() => response()->json(auth('api')->user()->notifications));
    Route::post('notifications/read',   fn() => tap(auth('api')->user()->unreadNotifications->markAsRead(), fn()=>response()->json(['success'=>true])));
    Route::get('currencies',            fn() => response()->json(\App\Models\Currency::all()));
});
