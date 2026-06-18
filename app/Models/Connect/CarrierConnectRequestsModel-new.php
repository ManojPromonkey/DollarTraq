<?php

namespace App\Models\Connect;

use App\Modules\Base\Models\BaseModel;

use Illuminate\Support\Facades\DB;

use App\Models\Carriers\CarriersModel;

use App\Models\System\ActivityLogsModel;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use App\Models\Carriers\CarrierAuthoritiesModel;

use App\Models\SMS\SMSModel;

class CarrierConnectRequestsModel extends BaseModel{

	protected $table = 'carrier_connect_requests';

	const STATUS_NEW = 'new';
	const STATUS_MAIL_VERIFIED = 'email_verified';
	const STATUS_MOBILE_VERIFIED = 'mobile_verified';
	const STATUS_STEP_ONE = 'step_one';
	const STATUS_STEP_TWO = 'step_two';
	const STATUS_STEP_THREE = 'step_three';

	protected $request_life = 72; // Hours

    function __construct(){

        $this->setTableIndex('row_id');
		$this->setTableName('carrier_connect_requests');

		$this->carriers_model = new CarriersModel;
		$this->activity_logs_model = new ActivityLogsModel;
	}

	public function format($row = false){

		if($row){

			$row->email_vefified = false;

			if($row->email_verified_on != null){

				$row->email_vefified = true;
			}

			$row->mobile_verified = false;

			if($row->mobile_verified_on != null){

				$row->mobile_verified = true;
			}

			unset($row->otp);
			unset($row->otp_sent_on);
			unset($row->otp_attempts);
			unset($row->last_otp_attempt);
		}

		return $row;
	}

    public function send_request($request, $user){

		$receiver_id = $request->post('receiver');

		if(!$receiver_id){

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}

		try {

			$_receiver = $this->carriers_model->fetch_row_by_id($receiver_id);

			if(!$_receiver || !$user){

				return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
			}

			/*
			Check if request already there
			*/
			$is_requested = self::where('receiver_id', $receiver_id)->where('sender_id', $user->row_id)->first();

			if($is_requested){

				/*
				Update the sent_on date to increase the expiry time
				*/

				$this->set_post_data('sent_on', date('Y-m-d H:i:s'));
				$this->set_post_data('updated_at', date('Y-m-d H:i:s'));

				$this->post_update($is_requested->row_id);
			}else{

				$this->set_post_data('sender_id', $user->row_id);
				$this->set_post_data('receiver_id', $receiver_id);
				$this->set_post_data('sent_on', date('Y-m-d H:i:s'));
				$this->set_post_data('status', self::STATUS_NEW);
				$this->set_post_data('created_at', date('Y-m-d H:i:s'));

				$this->post_save();
			}

			$act_data = [
				'carrier' => $receiver_id
			];

			$this->activity_logs_model->addActivity($user->row_id, 'broker', 'connect_request', 'carrier_onboarding', $act_data);

			/*
			Send email
			*/

			return ['status' => true, 'message' => 'Request has been sent successfully.'];

		}catch(\Throwable $e){

			Log::error('carrier connect_request failed: ' . $e->getMessage());

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}
	}

	public function connect_request_carrier($request, $user){

		$row_id = $request->post('row_id');

		if($row_id){

			$_request = $this->fetch_row_by_id($row_id);

			if($_request){

				$load_carrier = $this->load_carrier($_request->receiver_id);

				if($load_carrier['status']){

					$_request = $this->format($_request);

					return ['status' => true, 'row' => $load_carrier['row'], 'connect_request' => $_request];
				}
			}
		}

		return ['status' => false, 'message' => 'There was an error while processing your request.'];
	}

	public function validate_request($row_id = false){

		if(!$row_id){

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}

		try {

			$_request = $this->fetch_row_by_id($row_id);

			if(!$_request){

				return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
			}

			$carrier = $this->carriers_model->fetch_row_by_id($_request->receiver_id);

			if(!$carrier){

				return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
			}

			$expiry_date = Carbon::parse($_request->sent_on)->addHours($this->request_life);

			if(!$expiry_date->gt(Carbon::now())){

				return ['status' => false, 'action' => 'expired'];
			}

			$this->activity_logs_model->addActivity($carrier->row_id, 'carrier', 'connect_request', 'carrier_onboarding', ['source' => 'email_link']);

			if($_request->first_visit == null){

				$this->set_post_data('email_verified_on', date('Y-m-d H:i:s'));
				$this->set_post_data('first_visit', date('Y-m-d H:i:s'));
			}

			$this->set_post_data('updated_at', date('Y-m-d H:i:s'));
			$this->set_post_data('status', self::STATUS_MAIL_VERIFIED);

			$this->post_update($_request->row_id);

			return ['status' => true, 'request' => $_request];

		} catch (\Throwable $e) {

			Log::error('validate_request failed: ' . $e->getMessage());

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}
	}

	public function send_otp($request, $user){

		$row_id = $request->post('row_id');

		if(!$row_id){

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}

		try{

			$sms_model = new SMSModel;

			$_request = $this->fetch_row_by_id($row_id);

			if(!$_request){

				return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
			}

			$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

			$this->set_post_data('otp', $otp);
			$this->set_post_data('otp_sent_on', date('Y-m-d H:i:s'));

			$this->post_update($_request->row_id);

			$sms_model->carrier_connect_otp('+91 9990471132', $otp);

			$this->activity_logs_model->addActivity($_request->receiver_id, 'carrier', 'otp_request', 'carrier_onboarding');

			return ['status' => true, 'message' => 'OTP has been sent successfully.'];

		}catch(\Throwable $e){

			Log::error('otp_update failed: ' . $e->getMessage());

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}
	}

	public function verify_otp($request, $user){

		$row_id = $request->post('row_id');
		$otp = $request->post('otp');

		if(!$otp || !$row_id){

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}

		try{

			$_request = $this->fetch_row_by_id($row_id);

			if(!$_request){

				return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
			}

			/*
			OTP is valid for 15 minutes only
			*/
			if($_request->otp_sent_on == null){

				return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
			}

			$otp_sent_on = Carbon::parse($_request->otp_sent_on);

			if(!$otp_sent_on->gt(Carbon::now()->subMinutes(15))){

				$this->activity_logs_model->addActivity($_request->receiver_id, 'carrier', 'otp_expired', 'carrier_onboarding');

				$this->set_post_data('otp', '');
				$this->post_update($_request->row_id);
				
				return ['status' => false, 'message' => 'OTP has expired. Please try again.'];
			}

			/*
			Max 3 failed OTP attempts are allowed within 15 minutes.
			*/
			$total_attempted = 0;

			if($_request->last_otp_attempt != null){

				$last_attempt = Carbon::parse($_request->last_otp_attempt);

				$total_attempted = $_request->otp_attempts;

				/*
				Check if attempts are 3 and within 15 mintutes
				*/

				if($total_attempted >= 3 && $last_attempt->lt(Carbon::now()->subMinutes(15))){

					$this->activity_logs_model->addActivity($_request->receiver_id, 'carrier', 'otp_max_attempts', 'carrier_onboarding');
					return ['status' => false, 'message' => "Maximum verification attempts reached. Please try again after some time."];
				}
			}

			if($otp == $_request->otp){

				$this->set_post_data('status', self::STATUS_MOBILE_VERIFIED);

				$this->set_post_data('otp', '');
				$this->set_post_data('mobile_verified_on', date('Y-m-d H:i:s'));

				$this->post_update($_request->row_id);

				$this->activity_logs_model->addActivity($_request->receiver_id, 'carrier', 'otp_verified', 'carrier_onboarding');

				$load_carrier = $this->load_carrier($_request->receiver_id);

				if($load_carrier['status']){

					$_request = $this->format($_request);

					return ['status' => true, 'row' => $load_carrier['row'], 'connect_request' => $_request];
				}

				return ['status' => true, 'message' => 'OTP has been verified successfully.'];
			}else{

				return ['status' => false, 'message' => 'Please enter valid OTP.'];
			}

		}catch(\Throwable $e){

			Log::error('otp_update failed: ' . $e->getMessage());

			return ['status' => false, 'message' => 'Something went wrong. Please try again after sometime.'];
		}
	}

	public function load_carrier($row_id){

		$carrier_authorities_model = new CarrierAuthoritiesModel;

		$row = DB::table('carriers')
			->join($carrier_authorities_model->getTable(), 'carriers.dot_number', '=', 'carrier_authorities.dot_number')
			->select('carriers.*', 'carrier_authorities.docket_number')
			->where('carriers.row_id', '=', $row_id)
			->first();

		if($row){

			return ['status' => true, 'row' => $row];
		}

		return ['status' => false];
	}
}
