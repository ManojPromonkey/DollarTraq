<?php
namespace App\Http\Controllers\Connect;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\User;

use App\Models\Carriers\CarriersModel;
use App\Models\Connect\CarrierConnectRequestsModel;

use Illuminate\Support\Facades\Log;

class CarriersConnectController extends Controller
{
    
    public function validate(Request $request, $row_id){

        if($row_id){

            $row_id = trim($row_id);

            $carrier_connect_requests_model = new CarrierConnectRequestsModel;

            $carrier_request_response = $carrier_connect_requests_model->validate_request($row_id);

            if($carrier_request_response['status'] == true){

                return redirect()->away(config('app.broker_app') . "/carrier/connect/" . $row_id);
            }else{

                if(array_key_exists('action', $carrier_request_response) && $carrier_request_response['action'] == 'expired'){

                    return redirect()->away(config('app.broker_app') . "/carrier/invalid-access/");
                }
            }
        }else{

            return redirect()->away(config('app.broker_app') . "/carrier/invalid-access");
        }
    }

    public function handleWebhook(Request $request){

        $request_id = $request->input('vendor_data'); 
        $status = $request->input('status'); 

        $carrier_connect_requests_model = new CarrierConnectRequestsModel;

        $_connect_request = $carrier_connect_requests_model->fetch_row_by_id($row_id);

        if(!$_connect_request){
        
            Log::error('Didit webhook error. Request not exists. Request id: ' . $request_id);
            return response()->json(['error' => 'Profile not found'], 404);
        }

        $ipAnalysis = $request->input('ip_analysis', []);
        
        Log::error('didit response: ' . json_encode($ipAnalysis));

        $response_data = [];

        if(!empty($ipAnalysis)){
        
            $isVpnOrTor = $ipAnalysis['is_vpn_or_tor'] ?? false;
            $isDataCenter = $ipAnalysis['is_data_center'] ?? false;
            $detectedIp = $ipAnalysis['ip_address'] ?? null;
            $deviceInfo = ($ipAnalysis['device_brand'] ?? 'Unknown') . ' ' . ($ipAnalysis['device_model'] ?? '');
            $countryCode = $ipAnalysis['ip_country_code'] ?? null;

            $response_data = [
                'registration_ip' => $detectedIp,
                'registration_device' => trim($deviceInfo),
                'ip_country_isolated' => $countryCode,
                'uses_proxy_or_vpn' => $isVpnOrTor,
                'is_datacenter_origin' => $isDataCenter,
            ];

            if($isVpnOrTor || $isDataCenter){

                $response_data['risk_flagged'] = true;
            }
        }

        if($status === 'Approved'){

            $carrier_connect_requests_model->set_post_data('didit_status', $status);
            $carrier_connect_requests_model->set_post_data('didit_reponse_at', date('Y-m-d H:i:s'));
            $carrier_connect_requests_model->set_post_data('didit_response', json_encode($response_data));
            $carrier_connect_requests_model->post_update($request_id);

        }else{

            $carrier_connect_requests_model->set_post_data('didit_status', strtolower($status));
            $carrier_connect_requests_model->set_post_data('didit_reponse_at', date('Y-m-d H:i:s'));
            $carrier_connect_requests_model->post_update($request_id);
        }

        return response()->json(['status' => 'success']);
    }
}
