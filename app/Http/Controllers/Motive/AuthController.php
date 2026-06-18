<?php

namespace App\Http\Controllers\Motive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Motive\MotiveAuthService;

class AuthController extends Controller
{
    protected $service;

    public function __construct(MotiveAuthService $service)
    {
        $this->service = $service;
    }

    public function connect()
    {
        return redirect($this->service->authorizationUrl());
    }

    public function callback(Request $request)
    {
        dd($request->all());
        $this->service->storeToken($request->code);

        return response()->json([
            'status' => true,
            'message' => 'Motive Connected Successfully'
        ]);
    }
}