<?php
use Illuminate\Support\Facades\Route;
Route::prefix('install')->group(function(){
    Route::get('requirements', fn()=>response()->json(\App\Classes\start::checkRequirements()));
    Route::post('migrate',     fn()=>response()->json(['result'=>\Artisan::call('migrate')]));
    Route::post('seed',        fn()=>response()->json(['result'=>\Artisan::call('db:seed')]));
    Route::get('status',       fn()=>response()->json(['installed'=>file_exists(storage_path('installed'))]));
});
