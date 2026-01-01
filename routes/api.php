<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ResourceController;

Route::get('/hello', function (Request $request) {
    return response()->json(['message' => 'API works!']);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes with JWT authentication
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Reservations CRUD
    Route::apiResource('reservations', ReservationController::class);

    // Current user
    Route::get('/users/me', [UserController::class, 'me']);
    Route::put('/users/me', [UserController::class, 'updateMe']);

    // Admin user management (controllers should enforce admin checks)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Resources - list / detail / admin-managed resource CRUD
    Route::get('/resources', [ResourceController::class, 'index']);
    Route::get('/resources/{resource}', [ResourceController::class, 'show']);

    // Admin CRUD for resources (index/show above are public to authenticated users)
    Route::post('/resources', [ResourceController::class, 'store']);
    Route::put('/resources/{resource}', [ResourceController::class, 'update']);
    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy']);
});