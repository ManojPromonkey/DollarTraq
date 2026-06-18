<?php

namespace App\Models\Customers;

use App\Core\CoreModel;

use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use App\Models\Subscriptions\SubscriptionPlansModel;
use App\Models\Customers\CustomersSubscriptionsHistoryModel;

use App\Models\Payments\StripeModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class CustomersSubscriptionsModel extends CoreModel{
	use HasApiTokens, Notifiable, Authenticatable;
	const STATUS_PENDING = 0;
	const STATUS_ACTIVE = 2;
	const STATUS_PAST = 3;

	const PAYMENT_STATUS_PENDING = 0;
	const PAYMENT_STATUS_PAID = 1;

    	protected $table = 'customers_subscriptions';
	public $timestamps = false;


    	function __construct(){
        $this->setTableIndex('row_id');
	}

    	public function format($row = false){
		if($row){
			$customer->added_on_formatted 	= Carbon::parse($customer->added_on)->format('d M, Y');
		}

		return $row;
	}


	public function subscription_intent($request, $user){		
		$payment_method 	= $request->payment_method;
		$package_id 		= $request->package;
		$subscription_id 	= $request->subscription_id;

		$customers_model                         = new CustomersModel();

		$customers_subscriptions_history_model   = new CustomersSubscriptionsHistoryModel();

		$subscription_plans_model                = new SubscriptionPlansModel();

		$stripe_model                            = new StripeModel();

		$customer = CustomersModel::where('row_id', $user['row_id'])->first();

		$package = $subscription_plans_model->fetch_row_by_id($package_id);
		if($package){

			$subscription = $this->fetch_row_by_id($subscription_id);
			if($subscription){

				$package = $subscription_plans_model->format($package);

				$customer->address = $subscription->address;
				$customer->city = $subscription->city;
				$customer->pincode = $subscription->pincode;
				$customer->country = $subscription->country;

				if($subscription->state_id != ''){
					$customer->state = $subscription->state_id;
				}else{
					$customer->state = $subscription->state;
				}

				$intent = $stripe_model->create_order($customer, $package, $payment_method);

				if($intent['status'] == true){
					$intent_subscription = (object)$intent['subscription'];
					//file_put_contents('./subscription_intent.txt', json_encode($intent_subscription));
					file_put_contents(storage_path('app/subscription_intent.txt'), json_encode($intent_subscription));

					/* Update subscription id */
					$this->set_post_data('subscription_id', $intent_subscription->id);

					if(isset($intent_subscription->latest_invoice)){
						if($intent_subscription->latest_invoice->status == 'paid'){
							$this->set_post_data('status', self::STATUS_ACTIVE);
							$this->set_post_data('payment_status', self::PAYMENT_STATUS_PAID);
						}else{
							$this->set_post_data('status', self::STATUS_PENDING);
						}
					}

					/* Update subscription id */
					$this->set_post_data('subscription_id', $intent_subscription->id);

					if(isset($intent_subscription->latest_invoice)){
						if($intent_subscription->latest_invoice->status == 'paid'){
							$this->set_post_data('status', self::STATUS_ACTIVE);
							$this->set_post_data('payment_status', self::PAYMENT_STATUS_PAID);
						}else{
							$this->set_post_data('status', self::STATUS_PENDING);
						}
					}

					$this->set_post_data('updated_on', date('Y-m-d H:i:s'));
					$this->post_update($subscription->row_id);

					/* Update customer package */
					if(isset($intent_subscription->latest_invoice)){

						if($intent_subscription->latest_invoice->status == 'paid' || $intent_subscription->latest_invoice->status == 'open'){
						
							$customers_model->set_post_data('active_plan', $package->row_id);
							$customers_model->set_post_data('lifetime_loads', $package->loads_limit + $customer->lifetime_loads);
							$customers_model->set_post_data('subscription_id', $intent_subscription->id);

							$customers_model->post_update($customer->row_id);

							/*
							Update all the subscriptions as past
							*/
							self::where('customer_id', $customer->row_id)->where('row_id', '!=', $subscription->row_id)
							->update(['status' => self::STATUS_PAST,]);
						}
					}

					/*
					Add payment history
					*/

					$row_id = $customers_subscriptions_history_model->generate_unique_id('customers_subscriptions_history_model');

					$customers_subscriptions_history_model->set_post_data('row_id', $row_id);
					$customers_subscriptions_history_model->set_post_data('subscription_row_id', $subscription->row_id);
					$customers_subscriptions_history_model->set_post_data('subscription_id', $intent_subscription->id);
					$customers_subscriptions_history_model->set_post_data('customer_id', $customer->row_id);
					$customers_subscriptions_history_model->set_post_data('package_id', $package->row_id);

					$customers_subscriptions_history_model->set_post_data('address', $subscription->address);
					$customers_subscriptions_history_model->set_post_data('city', $subscription->city);
					$customers_subscriptions_history_model->set_post_data('state', $subscription->state);
					$customers_subscriptions_history_model->set_post_data('state_id', $subscription->state_id);
					$customers_subscriptions_history_model->set_post_data('pincode', $subscription->pincode);
					$customers_subscriptions_history_model->set_post_data('country', $subscription->country);

					$customers_subscriptions_history_model->set_post_data('added_on', date('Y-m-d H:i:s'));

					$customers_subscriptions_history_model->set_post_data('payment_status', self::PAYMENT_STATUS_PAID);

					$customers_subscriptions_history_model->post_save();

					return ['status' => true, 'subscription' => $intent_subscription];
				}else{
					return ['status'=>false, 'message' => $intent['error']];
				}
			}else{
				return ['status'=>false, 'message' => "Subscription details missing."];
			}
		}else{
			return ['status'=>false, 'message' => "Package details missing."];
		}
	}

}