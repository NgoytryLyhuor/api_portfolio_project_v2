<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/project',[ProjectController::class,'index']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::controller(ProjectController::class)->group(function() {
        Route::post('/project', 'store');
        Route::put('/project/{id}', 'update');
        Route::delete('/project/{id}', 'destroy');
    });
});
