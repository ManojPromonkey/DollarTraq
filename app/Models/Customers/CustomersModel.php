<?php

namespace App\Models\Customers;

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

use App\Models\CMS\CMSEmailModel;
use App\Models\Subscriptions\SubscriptionPlansModel;

use App\Models\Customers\CustomersUsersRolesModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;




class CustomersModel extends CoreModel
{
	use Authenticatable, HasApiTokens, Notifiable;
    	const STATUS_ENABLED = 1;
	const STATUS_DISABLED = 0;

    	protected $table = 'customers';
	public $timestamps = false;

	protected $appends = ['roles_data'];


    	function __construct(){
        $this->setTableIndex('row_id');
	}

	public function getRolesDataAttribute(){
		if (empty($this->roles)) {
			return collect();
		}

		$roleIds = array_filter(explode(',', $this->roles));
		return CustomersUsersRolesModel::whereIn('row_id', $roleIds)
		->get();
	}

    	public function format($customer = false){

		if($customer){
			$customer->added_on_formatted 	= Carbon::parse($customer->added_on)->format('d M, Y');

			$customer->name = clean_display($customer->name);

			$customer->first_name = ucwords(clean_display($customer->first_name));
			$customer->last_name = ucwords(clean_display($customer->last_name));

			$customer->profile_pic_url = '';
			if($customer->profile_pic != ''){
                	$customer->profile_pic_url = Storage::disk('public')->url('uploads/profile_pic/' . clean_display($customer->profile_pic));
			}

			$customer->role_names = '';
			if($customer->roles != ''){
				$roleIds = explode(',', $customer->roles);
				$roles = CustomersUsersRolesModel::whereIn('row_id', $roleIds)->get();
				$role_names = array();
				foreach($roles as $r){
					$role_names[] = $r->role_title;
				}

                	$customer->role_names = implode(', ', $role_names);

				//$customer->roles = explode(',', $customer->roles);
			}


			
			unset($customer->password);
		}

		return $customer;
	}


    /////////
    public function before_signup($post, $action, $fields, $user, $account_token, $_input_row_id=false){

		/* Check duplicate email */
		$duplicate_email = self::where('email', trim($post['email']));
		if($duplicate_email->count() > 0){
			die(json_encode(['status' => false, 'message' => 'Account with email address "' . $post['email'] . '" already exists. Please use different email address.']));
			//return ['status' => false, 'message' => 'Account with email address "' . $post['email'] . '" already exists. Please use different email address.'];
		}else{
			/* Check duplicate contact */
            	$duplicate_contact = self::where('c_code', trim($post['c_code']))->where('contact', trim($post['contact']));
			if($duplicate_contact->count() > 0){
				die(json_encode(['status' => false, 'message' => 'Account with contact number "' . $post['contact'] . '" already exists. Please use different contact number.']));
				//return ['status' => false, 'message' => 'Account with contact number "' . $post['contact'] . '" already exists. Please use different contact number.'];
			}else{
                $password = Hash::make(trim($post['password']));
                return ['password' => $password];
            }
		}
	}

	public function after_signup($request, $post_data = [], $row_id = false, $action = 'save', $user = false){

		if(array_key_exists('email', $post_data)){
			/* Email*/

			$first_name = $post_data['first_name'];
			$last_name = $post_data['last_name'];
			$email = $post_data['email'];
			
			$vars = [];
			$vars['customer_name'] = ucwords(clean_display($first_name) . ' ' . clean_display($last_name));
			$vars['sitename'] = sitename();
			$vars['customer_email'] = clean_display($email);
			$vars['application_url'] = application_url();

			$cms_email_model = new CMSEmailModel();
			$cms_email_model->send_template_email('customer_new_registration_email', $vars, $email, ucwords(clean_display($first_name) . ' ' . clean_display($last_name)));
		}
	}

	public function shipments_count($request, $user=false){
		//dd($user);
		//return $user['row_id'];
		if(isset($user['row_id'])){
			$customer = $this->fetch_row_by_id($user['row_id']);
			if($customer){
				if($customer->lifetime_loads > 0 && ($customer->lifetime_loads - $customer->consumed_loads) > 0){
					return true;
				}
			}
		}

		return false;
	}

	public function after_profile_update($request, $post_data = [], $row_id = false, $action = '', $user = false){
		
		//dd($request);
		if($request->hasFile('profile_pic')){
		

            $file = $request->file('profile_pic');
            $fieldName = 'profile_pic';

            $originalName = preg_replace('/[^a-zA-Z0-9.-]/', '', $file->getClientOriginalName());
            $extension = strtolower($file->getClientOriginalExtension());
            
            $filename = md5(now() . '-' . $originalName . '-' . random_string_generator(20)) . '.' . $extension;
            $directory_path = strtolower(random_string_generator(3)) . "/" . strtolower(random_string_generator(3));

            $upload_directory = 'profile_pic/';

            $storage_path = "uploads/" . $upload_directory . $directory_path . "/" . $filename;
            $db_save_path = $directory_path . "/" . $filename;

            if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){

                $image = Image::read($file->getRealPath());

                $fixSizeField = $fieldName . '_fix_size';

                if($request->has($fixSizeField) && $request->{$fixSizeField} != ''){

                    $image = encodeToTargetSize($image, 'jpg', $request->{$fixSizeField} * 1024);
                    Storage::put($storage_path, $image);
                }else{
                    Storage::put($storage_path, (string) $image->encodeByExtension($extension, 80));
                }
            }else{

                Storage::put($storage_path, file_get_contents($file->getRealPath()));
            }

            $image = $db_save_path;

		  self::where('row_id', $row_id)->update(['profile_pic' => $image]);
        	}

		$_customer = $this->fetch_row_by_id($row_id);
		if($_customer){
			$_customer = $this->format($_customer);
			return ['user' => $_customer];
		}

		return [];
	}

	public function sub_users_list($request, $user){
        $query = self::where('users_of', $user['row_id'])->orderBy('id', 'desc');
        //$query = $query->get();
        return $query;
	}

	public function user_save_before($post = [], $action = '', $fields = [], $user = false, $account_token = ''){

		$return = [];

		$return['users_of'] = $user['row_id'];

		if(isset($post['password'])){
            $password = Hash::make(trim($post['password']));
            $return['password'] = ($password);
		}

		return $return;
	}

	public function user_invite_before($post = [], $action = '', $fields = [], $user = false, $account_token = ''){

		$return = [];

		$return['users_of'] = $user['row_id'];
		$return['add_type'] = 'invite';

		if(isset($post['password'])){
            $password = Hash::make(trim($post['password']));
            //$return['password'] = ($password);
		}

		return $return;
	}

	public function user_invite_after($request, $post_data = [], $row_id = false, $action = 'save', $user = false){
		$customer = self::where('row_id', $row_id)->first();
		if(isset($customer->row_id) && $customer->add_type=='invite' && $customer->send_invite==0){
			$customer		= $this->format($customer);
			$name		= $customer->first_name.' '.$customer->last_name;
			$role_names	= $customer->role_names;
			$email		= $customer->email;
			$password_read	= rand(1000, 9999);

			$password = Hash::make(trim($password_read));

			$inviteLink	= '';
			$year		= date('Y');

			$html = '<!DOCTYPE html>
					<html>
					<head>
					<meta charset="utf-8">
						<title>DollarTraq Invitation</title>
					</head>
					<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,sans-serif;">

					<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;padding:40px 0;">
					<tr>
						<td align="center">

							<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">
								
								<!-- Header -->
								<tr>
									<td style="background:#155dfc;padding:30px;text-align:center;">
										<h1 style="color:#ffffff;margin:0;font-size:28px;">DollarTraq</h1>
										<p style="color:#dce4f8;margin-top:8px;">Freight Visibility Simplified</p>
									</td>
								</tr>

								<!-- Body -->
								<tr>
									<td style="padding:40px;">
										<h2 style="color:#1a1a1a;margin-top:0;">You are Invited!</h2>

										<p style="font-size:16px;color:#555;">Hello '.$name.',</p>

										<p style="font-size:16px;color:#555;line-height:1.7;"> You have been invited to join the <strong>DollarTraq</strong> platform. Please use the details below to access your account. </p>

									<table cellpadding="10" cellspacing="0" width="100%" style="background:#f8f9fc;border-radius:8px;margin:25px 0;"> 
										<tr> 
											<td width="180"><strong>Assigned Role:</strong></td> 
											<td>'. $role_names .' </td> 
										</tr> 
										
										<tr> 
											<td><strong>Username:</strong></td> 
											<td>'. $email .' </td> 
										</tr> 

										<tr> 
											<td><strong>Password:</strong></td> 
											<td>'. $password_read .' </td> 
										</tr> 
									</table>

									<div style="text-align:center;margin:35px 0;">
										<a href="'. $inviteLink .' "
											style="background:#0F62FE;
												color:#ffffff;
												text-decoration:none;
												padding:14px 30px;
												border-radius:8px;
												display:inline-block;
												font-size:16px;
												font-weight:bold;">
											Login Here
										</a>
									</div>

									<p style="font-size:14px;color:#777;">
										If the button doesnot work, copy and paste the following URL into your browser:
									</p>

									</td>
								</tr>

								<!-- Footer -->
								<tr>
									<td style="background:#f8f9fc;padding:20px;text-align:center;">
									<p style="margin:0;color:#888;font-size:13px;">
										© '. $year .'  DollarTraq. All rights reserved.
									</p>
									</td>
								</tr>

							</table>

						</td>
					</tr>
					</table>

					</body>
					</html>';

			//$admin_email = env('ADMIN_EMAIL');
			//echo $html;
			//dd($customer);
			Mail::send([], [], function ($message) use ($email, $html, $customer) {
				$message->to($email)
				->subject('DollarTraq Invitation')
				->html($html);
			});

			self::where('row_id', $row_id)->update([
				'send_invite'  => 1,
				'password'  => $password,
				'updated_on' => now(),
			]);
		}
	}

	///
	public function invite_mail($row_id){
		$customer = self::where('row_id', $row_id)->first();
		if(isset($customer->row_id)){
			

		}
	}

	public function customer_login($request, $user){
		//dd($request);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if($validator->fails()){
            return ['status' => false, 'message' => $validator->errors()];
        }

        $customer = self::where('email', $request->email)->first();
		//echo $password = Hash::make(trim($request->password));
		//exit;
        if(!$customer || !Hash::check($request->password, $customer->password)){
            return [
                'status' => false,
                'message' => 'The provided credentials do not match our records.',
            ];
        }

        if($customer->status==0){
            return [
                'status' => false,
                'message' => 'Your account is currently inactive..',
            ];
        }

        $token = $customer->createToken('customer_api_token')->plainTextToken;
        unset($customer->password);
        unset($customer->password_hash);
        unset($customer->email_verified_code);
        unset($customer->forgot_password_code);

	   self::where('row_id', $customer->row_id)->update(['last_login' => now(),]);
        return [
            'status' => true,
            'message' => 'Account logged in successfully',
            'customer' => $customer,
            'account_token' => $token, 
            'token_type' => 'Bearer',
        ];
    }


    public function update_password($request, $user){
		$password = $request->password;
		$new_password = $request->new_password;
		$confirm_password = $request->confirm_password;

		$customer = self::where('row_id', $user['row_id'])->first();
		//dd($new_password);
		if ($customer && $password != '' && $new_password != '' && $confirm_password != '') {

			// Old password check
			if(Hash::check($password, $customer->password)){

				if ($new_password === $confirm_password) {

					$customer->password = Hash::make(trim($new_password));
					$customer->forgot_password_code = '';
					$customer->forgot_password_datetime = '0000-00-00 00:00:00';
					$customer->updated_on = now();

					$customer->save();

					return [
						'status'  => true,
						'message' => 'Password has been updated successfully. Please login into your account.'
					];

				} else {

					return [
						'status'  => false,
						'message' => 'Password must be same as confirm password.'
					];
				}

			} else {

				return [
					'status'  => false,
					'message' => 'Invalid Old password.',
				];
			}				

		} else {

			return [
				'status'  => false,
				'message' => 'Input fields missing.'
			];
		}
	}

	///
	public function load_customer($request, $user){
		$subscription_plans_model = new SubscriptionPlansModel();
		
		$page = $request->page;

		$customer = self::where('row_id', $user['row_id'])->first();
		if ($customer) {

			$customer = $this->format($customer);

			if($customer->active_plan != ''){
				$plan = $subscription_plans_model->fetch_row_by_id($customer->active_plan);
				if($plan){
					$plan = $subscription_plans_model->format($plan);
				}

				$customer->plan = $plan;
			}

			$response = [];
			$response['status'] = true;
			$response['customer'] = $customer;

			if($page == 'subscriptions'){

				$subscription_plans = SubscriptionPlansModel::where('status', 1);
				$_subscription_plans = [];
				if($subscription_plans->count() > 0){
					$subscription_plans = $subscription_plans->get();
					foreach($subscription_plans as $subscription_plan){
						$_subscription_plans[] = $subscription_plans_model->format($subscription_plan);
					}
				}

				$response['plans'] = $_subscription_plans;
			}
						
			return $response;
		} else {

			return [ 'status'  => false, 'message' => 'No Accounts.', 'code' => 'no_accounts.' ];
		}
	}

}
