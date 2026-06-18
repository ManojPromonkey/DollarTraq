<?php

namespace App\Models\Drivers;

use App\Core\CoreModel;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\URL;


use App\Models\Shipments\ShipmentsModel;
use App\Models\Shipments\ShipmentsStopsModel;
use App\Models\Shipments\ShipmentsUpdatesModel;
use App\Models\Shipments\ShipmentsCarriersModel;
use App\Models\Shipments\ShipmentsTrackingMethodsModel;
use App\Models\Shipments\ShipmentsActionCentreModel;
use App\Models\Shipments\ShipmentsDocuments;


use App\Models\Customers\CustomersModel;
use App\Models\Chats\ChatsModel;
use App\Models\CMS\CMSEmailModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DriversModel extends CoreModel
{
	use Authenticatable, HasApiTokens, Notifiable;

	const STATUS_ENABLED = 1;
	const STATUS_DISABLED = 0;

    	protected $table = 'drivers';
	public $timestamps = false;

    	function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($driver = false){
		
		if($driver){

			if(isset($driver->added_on)){
				$driver->added_on_formatted 	= Carbon::parse($driver->added_on)->format('d M, Y');
			}

			if(property_exists($driver, 'name')){
				$driver->name = clean_display($driver->name);
			}

			$driver->profile_pic_url = '';
			if(property_exists($driver, 'profile_pic')){
				if($driver->profile_pic !== ''){
					$driver->profile_pic_url = URL::to(Storage::url("uploads/drivers/".clean_display($driver->profile_pic)));
				}
			}

			if(property_exists($driver, 'carrier_title')){
				$driver->carrier_title = clean_display($driver->carrier_title);
			}

			if(property_exists($driver, 'tracking_method_title')){
				$driver->tracking_method_title = clean_display($driver->tracking_method_title);
			}

			if(property_exists($driver, 'carrier_title')){

				$driver->carrier_title = clean_display($driver->carrier_title);
				$driver->shippment_carrier_label = ucwords(clean_display($driver->carrier_title));
				
				$carrier_title_placeholder = substr(clean_display($driver->carrier_title), 0, 1);

				$title_div = explode(' ', $driver->carrier_title);

				if(count($title_div) == 1){
					$carrier_title_placeholder .= ' ' . substr(clean_display($title_div[1]), 0, 1);
				}else{
					$carrier_title_placeholder .= ' ' . substr(clean_display($driver->carrier_title), 1, 1);
				}

				$driver->carrier_title_placeholder = strtoupper($carrier_title_placeholder);

				$driver->shipment_date = date("d M Y", strtotime($driver->added_on));
				$driver->shipment_time = date("H:i A", strtotime($driver->added_on));

				$shipments_model	= new ShipmentsModel();
				$statuses = $shipments_model->status();

				$driver->status_label = $this->key_filter($statuses, $driver->status);
			}

			unset($driver->password);
		}

		return $driver;
	}

	public function country_codes(){
		return [
			['key' => 'canada', 'label' => 'Canada', 'code' => 1],
			['key' => 'india', 'label' => 'India', 'code' => 91],
			['key' => 'uk', 'label' => 'UK', 'code' => 44],
			['key' => 'usa', 'label' => 'USA', 'code' => 1],
		];
	}
	
	public function loads_list($request, $user){
		$driver_row_id = $request->post('driver_row_id', $user['row_id']);
		$status = $request->post('status', '');

		$query = ShipmentsModel::with([
			'carrier:row_id,title',
			'trackingMethod:row_id,title',
			'stops'
		])->where('driver', $driver_row_id);

		if ($status != '') {

			if($status === 'new') {
				$query->whereIn('status', [ShipmentsModel::STATUS_ACTIVE, 0]);
			}

			if($status === 'active') {
				$query->whereIn('status', [ShipmentsModel::STATUS_AWAITING_PICKUP, ShipmentsModel::STATUS_IN_TRANSIT]);
			}

			if ($status === 'upcoming') {
				$query->where(function ($q) {
					$q->where('status', 0)->whereDate('added_on', '>=', now()->toDateString());
				});
			}

			if ($status === 'completed') {
				$query->where('status', ShipmentsModel::STATUS_DELIVERED);
			}
		}

		$loads = $query->get();

		return $loads->map(function ($load) {

			$load->carrier_title = $load->carrier?->title;
			$load->tracking_method_title = $load->trackingMethod?->title;

			$load->pickups = $load->stops->where('stop_type', 'pickup')->values();

			$load->drop_offs = $load->stops->where('stop_type', 'drop_off')->values();

			unset($load->carrier);
			unset($load->trackingMethod);
			unset($load->stops);

			return $this->format($load);
		})->values();
	}
	
	
	public function driver_stats($row_id = false){

		$new_loads = 0;
		$active_loads = 0;
		$upcoming_loads = 0;
		$completed_loads = 0;

		if($row_id){

			$shipments_model 	= new ShipmentsModel();
			$driver_shipments = ShipmentsModel::where('driver', $row_id);  

			if($driver_shipments->count() > 0){

				$driver_shipments = $driver_shipments->get();

				foreach($driver_shipments as $driver_shipment){
					if($driver_shipment->status == $shipments_model::STATUS_PENDING){
						$new_loads = $new_loads + 1;
					}

					if($driver_shipment->status == $shipments_model::STATUS_ACTIVE || $driver_shipment->status == $shipments_model::STATUS_AWAITING_PICKUP || $driver_shipment->status == $shipments_model::STATUS_IN_TRANSIT){
						$active_loads = $active_loads + 1;
					}

					if($driver_shipment->status == $shipments_model::STATUS_DELIVERED){
						$completed_loads = $completed_loads + 1;
					}
				}
			}
		}

		return [$active_loads, $upcoming_loads, $completed_loads, $new_loads];
	}



	//////
	public function drivers_update($request, $user){
		$name = $request->name;
		$email = $request->email;

		$error = false;
		$error_message = '';

		$driver = self::where('row_id', $user['row_id'])->first();

		if($email != ''){
			$driver_email = self::where('row_id', '!=', $driver->row_id)->where('email', $email);
			if($driver_email->get() > 0){
				$error = true;
				$error_message = 'Email is used by other user.';
			}
		}
		
		if($error){
			return ['status' => false, 'message' => $error_message];
		}else{
			$this->set_post_data('name', $name);
			if($email != ''){
				$this->set_post_data('email', $email);
			}

			$this->post_update($driver->row_id);

			$driver = $this->fetch_row_by_id($driver->row_id);
			$driver = $this->format($driver);

			return ['status'=>true, 'message'=>'Information updated successfully.', 'user' => $driver];
		}
	}


	public function drivers_login($request, $user){
		//dd($request);

        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'password' => 'required',
		  'source' => 'required',
		  'token' => 'required',
        ]);

        if($validator->fails()){
            return ['status' => false, 'message' => $validator->errors()];
        }

        $driver = self::where('mobile', $request->mobile)->first();
        if(!$driver || !Hash::check($request->password, $driver->password)){
            return ['status' => false, 'message' => 'The provided credentials do not match our records.',];
        }
	
        if($driver->status!=self::STATUS_ENABLED){
            return ['status' => false, 'code'=>'account_inactive', 'message'=>'Your account is not active!!'];
        }else{
			$source = $request->source;
			$token = $request->token;

			/* Update token and device*/
			$this->set_post_data('device', $source);
			$this->set_post_data('token', $token);

			$this->post_update($driver->row_id);
			
			//$account_token = $this->drivers_auth_model->generate_token($driver->id);
			$account_token = $driver->createToken('driver_api_token')->plainTextToken;

			$driver = $this->format($driver);

			$google_map_api_key = 'AIzaSyAh26m8Ce0wsHBi8RTh3B6oC00sUz43WKU';

			if($source == 'ios'){
				/* iOS Google Map APi Key */
				$google_map_api_key = 'AIzaSyAPrXuaOvgMiycRvTziYNrUwpuGVWwTY-A';
			}

			return ['status'=>true, 'message'=>'Login Successful!!', 'account_token'=>$account_token, 'user'=>$driver, 'google_map_api' => $google_map_api_key];
	   }

    }


    public function otp_send($request, $user){
		//dd($request);

        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
        ]);

        if($validator->fails()){
            return ['status' => false, 'message' => $validator->errors()];
        }

        $driver = self::where('mobile', $request->mobile)->first();
        if(!$driver){
            return ['status' => false, 'code'=>'no_account', 'message' => 'No account available associated with the given mobile number!',];
        }
	
        if($driver->status!=self::STATUS_ENABLED){
            return ['status' => false, 'code'=>'account_inactive', 'message'=>'Your account is not active!!'];
        }else{
			
	   		$otp = random_string_generator(4, 'numeric');
			$this->set_post_data('otp', $otp);
			$this->set_post_data('otp_sent_on', date('Y-m-d H:i:s'));

			$this->post_update($driver->row_id);

			return ['status'=>true, 'message'=>'OTP has been sent successfully.'];
				
	   }

    }


    public function verify_otp($request, $user){
		//dd($request);

		$validator = Validator::make($request->all(), [
			'mobile' => 'required',
			'otp' => 'required',
			'source' => 'required',
			'token' => 'required',
		]);

		if($validator->fails()){
			return ['status' => false, 'message' => $validator->errors()];
		}

		$driver = self::where('mobile', $request->mobile)->first();
		if(!$driver){
			return ['status' => false, 'code'=>'no_account', 'message' => 'No account available associated with the given mobile number!',];
		}

	   	$source = $request->source;
		$token = $request->token;
		$otp = $request->otp;
	
		if($driver->otp == $otp){
				
			$this->set_post_data('otp', '');
			$this->set_post_data('otp_sent_on', NULL);

			$this->set_post_data('device', $source);
			$this->set_post_data('token', $token);

			$this->post_update($driver->row_id);

			//$account_token = $this->drivers_auth_model->generate_token($driver->id);
			$account_token = $driver->createToken('driver_api_token')->plainTextToken;

			$driver = $this->format($driver);

			$google_map_api_key = 'AIzaSyAh26m8Ce0wsHBi8RTh3B6oC00sUz43WKU';

			if($source == 'ios'){
				/* iOS Google Map APi Key */
				$google_map_api_key = 'AIzaSyAPrXuaOvgMiycRvTziYNrUwpuGVWwTY-A';
			}

			return ['status'=>true, 'message'=>'Login Successful!!', 'account_token'=>$account_token, 'user'=>$driver, 'google_map_api' => $google_map_api_key];
					
		}else{
			return ['status'=>false, 'message'=>'The OTP you entered is invalid.'];
		}

    }


    	public function logout($request, $user){
		$user->currentAccessToken()->delete();

		return response()->json([
			'status' => true,
			'message' => 'Logged out successfully.'
		]);
	}


	///
	public function drivers_signup($request, $user){
		//dd($request);

		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'email' => 'required',
			'mobile' => 'required',
			'password' => 'required',
		]);

		if($validator->fails()){
			return ['status' => false, 'message' => $validator->errors()];
		}

		$driver = self::where('mobile', $request->mobile)->first();
		if($driver){
			return ['status' => false, 'code'=>'account_exists', 'message' => 'Account with this mobile number is already created!',];
		}

		$driver = self::where('email', $request->email)->first();
		if($driver){
			return ['status' => false, 'code'=>'account_exists', 'message' => 'Account with this email address is already created!',];
		}

	   	$name = $request->name;
		$email = $request->email;
		$c_code = $request->c_code;
		$mobile = $request->mobile;
		$password = $request->password;
	
		$row_id = $this->generate_unique_id();

		$this->set_post_data('row_id', $row_id);
		$this->set_post_data('name', $name);
		$this->set_post_data('email', $email);
		if($c_code && $c_code!=''){
			$this->set_post_data('c_code', $c_code);
		}
		$this->set_post_data('mobile', $mobile);
		$this->set_post_data('password', Hash::make($password));

		$this->set_post_data('status', self::STATUS_ENABLED);
		$this->set_post_data('added_on', date('Y-m-d H:i:s'));

		$this->post_save();

		return ['status'=>true, 'message'=>'Account created successfully. Login into your account.'];

    }


    public function loads_init($request, $user){
		$driver = $this->where('row_id', $user['row_id'])->first();
		//dd($driver);
		list($active_loads, $upcoming_loads, $completed_loads, $new_loads) = $this->driver_stats($driver->row_id);

		/* Action centre requests */
		$shipments_action_centre_model	= new ShipmentsActionCentreModel();
		$_load_requests = $shipments_action_centre_model->driver_load_requests($driver->row_id);
			
		return ['status' => true, 'new_loads' => $new_loads, 'active_loads' => $active_loads, 'upcoming_loads' => $upcoming_loads,
			'completed_loads' => $completed_loads, 'load_requests' => $_load_requests, 'message' => 'Request sent successfully.'
		];
	}


	public function shipment_accept($request, $user){
		$driver = self::where('row_id', $user['row_id'])->first();

		$shipment_row_id  = $request->filled('shipment_row_id') ? $request->post('shipment_row_id') : '';
		
		$shipments_model				= new ShipmentsModel();
		$shipments_action_centre_model	= new ShipmentsActionCentreModel();
		$customers_model				= new CustomersModel();
		$chats_model					= new ChatsModel();
		$cms_email_model				= new CMSEmailModel();
		

		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);

		if($shipment){
			$driver = $this->fetch_row_by_field('mobile', $shipment->tracking_full_number);

			if($driver){

				$shipments_model->set_post_data('driver', $driver->row_id);
				$shipments_model->post_update($shipment->row_id);

				$shipments_action_centre_model->set_post_data('app_status', 1);
				$shipments_action_centre_model->set_post_data('request_status', 1);
				$shipments_action_centre_model->set_post_data('request_updated_on', date('Y-m-d H:i:s'));
				$shipments_action_centre_model->post_update($shipment_row_id, 'shipment_id');

				/*
				Update drivers shipment stats
				*/
				list($active_loads, $upcoming_loads, $completed_loads, $new_loads) = $this->driver_stats($driver->row_id);

				$this->set_post_data('active_loads', $active_loads);
				$this->set_post_data('upcoming_loads', $upcoming_loads);
				$this->set_post_data('completed_loads', $completed_loads);

				$this->post_update($driver->row_id);

				/*
				Action centre requests
				*/

				$_load_requests = $shipments_action_centre_model->driver_load_requests($driver->row_id);

				$customer = $customers_model->fetch_row_by_id($shipment->customer);

				if($customer){
					/*
					Send email
					*/
					$vars = [];
					$vars['customer_name'] = ucwords(clean_display($customer->first_name) . ' ' . clean_display($customer->last_name));
					$vars['sitename'] = sitename();
					$vars['shipment_number'] = clean_display($shipment->shipment_number);
					$vars['driver_name'] = ucwords(clean_display($driver->name));


					$cms_email_model->send_template_email('driver_shipment_acceptance', $vars, $customer->email, ucwords(clean_display($customer->first_name) . ' ' . clean_display($customer->last_name)));

					/*
					CC Email
					*/
					$email_updates_to = $shipment->email_updates_to;

					if($email_updates_to != ''){

						$email_updates_to = @explode(',', clean_display($email_updates_to));

						if(is_array($email_updates_to)){

							foreach($email_updates_to as $email_update_to){

								if($email_update_to != ''){
								
									$cms_email_model->send_template_email('driver_shipment_acceptance', $vars, trim($email_update_to), "User");
								}
							}
						}
					}
				}

				return ['status' => true, 'new_loads' => $new_loads, 'active_loads' => $active_loads,
						'upcoming_loads' => $upcoming_loads, 'completed_loads' => $completed_loads, 
						'load_requests' => $_load_requests, 'message' => 'Request sent successfully.'
					];
			}else{
				return ['status'=>false, 'message' => 'Information missing.'];
			}
		}else{
			return ['status'=>false, 'message' => 'Information missing.'];
		}

	}


	public function shipment_documents($request, $user){
		$driver = self::where('row_id', $user['row_id'])->first();

		$shipment_row_id  = $request->filled('shipment_row_id') ? $request->post('shipment_row_id') : '';
		
		$shipments_model		= new ShipmentsModel();
		$shipments_documents	= new ShipmentsDocuments();

		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);

		if($shipment){
			$documents = $shipments_documents->load_documents($shipment->row_id);
			return ['status' => true, 'documents' => $documents];
		}else{
			return ['status'=>false, 'message' => 'Information missing.'];
		}

	}


	public function shipment_timeline($request, $user){
		$driver = self::wherewhere('row_id', $user['row_id'])->first();

		$shipment_row_id  = $request->filled('shipment_row_id') ? $request->post('shipment_row_id') : '';
		
		$shipments_model				= new ShipmentsModel();

		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);

		if($shipment){

			$shipment = $shipments_model->format($shipment);

			$shipment_timeline = $shipments_model->shipment_timeline($shipment);

			return ['status' => true, 'shipment_timeline' => array_values($shipment_timeline),];
		}else{
			return ['status'=>false, 'message' => 'Information missing.'];
		}

	}

	

    
}
