<?php

namespace App\Http\Controllers\Motive;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Motive Webhook', $request->all());

        dispatch(function () use ($request) {

            $event = $request->event;

            switch($event){

                case 'vehicle_location_updated':

                    // update location

                break;

                case 'fault_code_opened':

                    // maintenance alert

                break;
            }
        });

        return response()->json([
            'success' => true
        ]);
    }
}