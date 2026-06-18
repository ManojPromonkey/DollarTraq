<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

use App\Models\Auth\DiditModel;

use App\Models\Customers\CustomersModel;

class DiditController extends Controller
{

    public function createSession(Request $request, DiditModel $didit_model){

        $user_id = "8d8c59bfc84b90b988aee7a68b44d23c";

        $response = Http::withHeaders([
            'x-api-key' => $didit_model::DIDIT_API_KEY,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://verification.didit.me/v3/session/', [
            'workflow_id' => $didit_model::DIDIT_WORKFLOW_ID,
            'vendor_data' => (string) $user_id,
            'callback' => url('/signup/verification-callback'),
        ]);

        if($response->failed()){

            return response()->json(['status' => false, 'message' => 'Failed to initialize verification workflow.'], 500);
        }

        $data = $response->json();

        return response()->json(['status' => true, 'url' => $data['url'] ?? null, 'session_id' => $data['session_id'] ?? $data['session_id'] ?? null]);
    }

    public function handleWebhook(Request $request, CustomersModel $customers_model){

        $user_id = $request->input('vendor_data'); 
        $status = $request->input('status');

        $user = $customers_model->fetch_row_by_id($user_id);

        if($user){

            if($status === 'Approved'){

                $user->update([
                    'identity_verified_at' => now(),
                    'verification_status' => 'verified'
                ]);
            }else{

                $user->update([
                    'verification_status' => 'failed'
                ]);
            }

            return response()->json(['status' => 'success']);
        }

        return response()->json(['error' => 'User not found'], 404);
    }
}