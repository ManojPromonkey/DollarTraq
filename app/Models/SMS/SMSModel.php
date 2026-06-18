<?php

namespace App\Models\SMS;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class SMSModel extends CoreModel
{

    protected $table = 'queries';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function app_install_sms($to){

		$app_url = "https://play.google.com/store/apps/details?id=com.DollarTraq&hl=en_IN";

		$body = "Hello, please install the app using link {$app_url}";

		return $this->send_sms($to, $body);
	}
	
	public function send_sms($to, $message_body){

		$app_username = 'kush.gupta@ktrfreight.com';
		$app_key = 'F2EB305D-DCFC-A5C4-8634-645F1CE6814E';
    
		$payload = [
				'messages' => [
					[
						'to' => $to,
						'body' => $message_body,
						'source' => 'sdk'
					]
				],
				'url_shortening' => true
			];

			try{

				$ch = curl_init("https://rest.clicksend.com/v3/sms/send");

				curl_setopt($ch, CURLOPT_HTTPHEADER, [
					'Content-Type: application/json',
					'Authorization: Basic ' . base64_encode($app_username . ":" . $app_key)
				]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

				$response = curl_exec($ch);
				$err = curl_error($ch);

				curl_close($ch);

				if($err){

					return ['status' => 'error', 'message' => "Error: " . $err];
				}else{

					$response = json_decode($response);

					if(!is_object($response)){

						if(isset($response->http_code) && $response->http_code == 200 && $response->response_code == 'SUCCESS'){

							return [
								'status'     => 'success',
								'message_id' => $response->message_id,
								'cost'       => $response->message_price,
								'currency'   => $response->data->_currency->currency_name_short,
								'info'       => 'Message queued successfully'
							];
						}else{

							$error_msg = isset($response->response_msg) ? $response->response_msg : 'Unknown API Error';

							return ['status' => 'error', 'message' => $error_msg];
						}
					}else{

						return ['status' => 'error', 'message' => "There was an error while processing your request."];
					}

					return ['status' => 'success', ];
				}

			}catch(\Exception $e){

				return ['status' => 'error', 'message' => $e->getMessage()];
			}
		}
    
}
