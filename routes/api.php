<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// use App\Http\Controllers\Apis\Auth\AuthController;
// use App\Http\Controllers\Apis\Factory\HandleController;

use App\Http\Controllers\Auth\DiditController;

// Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/carriers/didit/auth', [DiditController::class, 'createSession']);
Route::post('/carriers/didit/auth/handle', [DiditController::class, 'handleWebhook']);

// Route::post('/handle/{any}', [HandleController::class, 'handler'])->where('any', '.*');