<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TravelOrderController;
use Illuminate\Support\Facades\Route;

// Rotas Públicas (Auth)
Route::post('login', [AuthController::class, 'login']);

// Rotas Protegidas
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // CRUD de Pedidos
    Route::apiResource('travel-orders', TravelOrderController::class);
    Route::patch('travel-orders/{travelOrder}/status', [TravelOrderController::class, 'updateStatus']);
});