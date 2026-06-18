<?php

namespace App\Models\Payments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\CMS\CMSEmailModel;
use App\Models\Customers\CustomersModel;



class PaymentModel extends CoreModel
{
    	const STATUS_CANCEL = 0;
	const STATUS_PROCESSING = 1;
	const STATUS_DELIVERED = 2;
	const STATUS_PICKUP_REQUESTED = 3;  


    protected $table = 'sales_payments';

    function __construct(){
        $this->setTableIndex('row_id');
	}

   	public function payment_providers(){

		$provider = [];

		$provider[] = [
			'model' => 'payment/cod_model'
		];

		$provider[] = [
			'model' => 'payment/razorpay_model'
		];

		$provider[] = [
			'model' => 'payment/instamojo_model'
		];

		$provider[] = [
			'model' => 'payment/authorize_net_model'
		];

		$provider[] = [
			'model' => 'payment/paypal_model'
		];

		return $provider;
	}

	public function payment_methods($method = false, $cart_id = false, $cart = false, $totals = [], $include_all = false){

		$providers = $this->payment_providers();

		$methods = [];

		foreach($providers as $provider){

			if(array_key_exists('model', $provider)){

				$model_path = $provider['model'];
				$primary_model = trim($provider['model']);

				if(stristr($model_path, '/')){

					$_model = explode('/', $model_path);
	
					/*
					Update primary model
					*/
					$primary_model = trim($_model[1]);
				}

				$this->load->model($provider['model']);

				$is_removed = false;

				if(defined("$primary_model::remove")){

					if($primary_model::remove == '1'){

						$is_removed = true;
					}
				}

				if($include_all){

					$is_removed = false;
				}

				if(!$is_removed){

					$methods[$primary_model::code] = [
						'code' => $primary_model::code,
						'label' => $this->{$primary_model}->get_label(),
						'status' => $this->{$primary_model}->get_status(),
						'details' => $this->{$primary_model}->get_details(),
						'price' => method_exists($primary_model, "get_price") ? $this->{$primary_model}->get_price($cart, $totals) : '',
						'totals_label' => method_exists($primary_model, "get_totals_label") ? $this->{$primary_model}->get_totals_label() : 'Payment charges',
					];
				}
			}
		}

		if($method){

			if(array_key_exists($method, $methods)){

				return $methods[$method];
			}

			return false;
		}

		return array_values($methods);
	}

	public function methods(){

		$methods = $this->payment_methods();

		$methods_list = [];

		foreach($methods as $method){

			$methods_list[] = ['key' => $method['code'], 'value' => $method['label'], 'status' => $method['status'], 'details' => $method['details']];
		}

		return $methods_list;
	}
    
}
