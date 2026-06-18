<?php

namespace App\Http\Controllers\Motive;

use App\Http\Controllers\Controller;
use App\Services\Motive\MotiveApiService;

class LocationController extends Controller
{
    public function index()
    {
        $motive = app(MotiveApiService::class);

        $locations = $motive->get('/v3/vehicle_locations');

        return response()->json($locations);
    }
}