<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ETAController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Grupo Rino E.I.R.L. Delivery Platform
|--------------------------------------------------------------------------
*/

// Rutas Públicas de Autenticación
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Predicción de ETA mediante Machine Learning (IA)
Route::post('/predict-eta', [ETAController::class, 'predictEta']);

// Rutas de Catálogo de Productos y Categorías
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Rutas de Gestión de Pedidos (Accesibles para App Cliente y Chatbot LLM)
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders', [OrderController::class, 'store']);
Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::put('/orders/{id}/assign-driver', [OrderController::class, 'assignDriver']);

// Componente IoT: Telemetría y Rastreo Satelital GPS
Route::post('/telemetry/location', [TelemetryController::class, 'storeLocation']);
Route::get('/telemetry/latest/{driverId}', [TelemetryController::class, 'latestDriverLocation']);
Route::get('/telemetry/order/{orderId}', [TelemetryController::class, 'orderTracking']);

// Rutas Protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Operaciones Administrativas
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Chat con Inteligencia Artificial (Google Gemini)
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [ChatController::class, 'listConversations']);
        Route::post('/conversations', [ChatController::class, 'createConversation']);
        Route::get('/conversations/{id}/messages', [ChatController::class, 'getMessages']);
        Route::post('/conversations/{id}/messages', [ChatController::class, 'sendMessage']);
        Route::delete('/conversations/{id}', [ChatController::class, 'deleteConversation']);
    });
});
