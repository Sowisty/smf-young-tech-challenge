<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceApiController;
use App\Http\Controllers\Api\AuthController;

// Publiczne - dla każdego
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Chronione - tylko z Bearer Tokenem (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/invoices', [InvoiceApiController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceApiController::class, 'show']);
    Route::post('/invoices/upload', [InvoiceApiController::class, 'upload']);
    Route::delete('/invoices/{id}', [InvoiceApiController::class, 'destroy']);
});