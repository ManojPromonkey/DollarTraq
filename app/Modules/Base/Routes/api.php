<?php

use Illuminate\Support\Facades\Route;

use App\Modules\Base\Controllers\Apis\Auth\AuthController;

use App\Modules\Base\Controllers\Factory\HandleController;

Route::post('/backend/auth/login', [AuthController::class, 'login']);

Route::post('/handle/{any}', [HandleController::class, 'handler'])->where('any', '.*');