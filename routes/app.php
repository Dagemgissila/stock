<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Common\DashboardController;

Route::middleware(['auth.api'])->group(function () {
    Route::get('notifications',          [DashboardController::class, 'notifications']);
    Route::post('notifications/{id}/read',[DashboardController::class, 'markRead']);
    Route::post('notifications/read-all',[DashboardController::class, 'markAllRead']);
});
