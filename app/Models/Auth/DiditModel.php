<?php

namespace App\Models\Auth;

use App\Modules\Base\Models\BaseModel;
use App\Models\Connect\CarrierConnectRequestsModel;

class DiditModel extends BaseModel
{

	const DIDIT_API_KEY = "8QNzF6lDV6G7abtKAt9BDeSIc6M9348Ry2uS5YQvOeI";
	const DIDIT_WORKFLOW_ID = '152e4704-3af0-4d6f-a2cc-7d8e9ca46aae';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function createSession($request, $user){

		$row_id = $request->post('row_id');

        // $user_id = "8d8c59bfc84b90b988aee7a68b44d23c";

        $response = Http::withHeaders([
            'x-api-key' => self::DIDIT_API_KEY,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://verification.didit.me/v3/session/', [
            'workflow_id' => self::DIDIT_WORKFLOW_ID,
            'vendor_data' => (string) $row_id,
            'callback' => config('app.broker_app') . "/carrier/connect/" . $row_id,
        ]);

        if($response->failed()){

            return ['status' => false, 'message' => 'Failed to initialize verification workflow.'];
        }

        $data = $response->json();

		$carrier_connect_requests_model = new CarrierConnectRequestsModel;

		$carrier_connect_requests_model->set_post_data('didit_session_id', $data['session_id'] ?? $data['session_id'] ?? '');
		$carrier_connect_requests_model->set_post_data('created_at', date('Y-m-d H:i:s'));
		$carrier_connect_requests_model->post_update($row_id);

        return ['status' => true, 'url' => $data['url'] ?? null, 'session_id' => $data['session_id'] ?? $data['session_id'] ?? null];
    }
}
