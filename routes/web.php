<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Connect\CarriersConnectController;

Route::get('/', function () {
    return view('welcome');
});

/*
Carrier OnBoarding
*/
Route::get('/carriers/connect/request/{row_id}', [CarriersConnectController::class, 'validate']);

/*
Didit connect
*/
Route::post('/carriers/connect/didit/webhook', [DiditVerificationController::class, 'handleWebhook']);