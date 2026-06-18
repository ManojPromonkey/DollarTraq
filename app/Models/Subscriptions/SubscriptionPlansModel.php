<?php

namespace App\Models\Subscriptions;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\Payments\StripeModel;

use Illuminate\Support\Carbon;

class SubscriptionPlansModel extends CoreModel
{
    const STATUS_ACTIVE = 1;

    protected $table = 'subscription_plans';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($row = false){

		if($row){

			if(property_exists($row, 'added_on')){
				$row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M Y');
			}

			$row->title = clean_display($row->title);
			$row->sub_title = clean_display($row->sub_title);
			$row->details = clean_display($row->details);

			$row->billing_period_label = "Monthly";

			$row->final_price = 0;
			if($row->is_demo == 0){
				$row->final_price = $row->special_price > 0 ? $row->special_price : $row->price;
			}
		}

		return $row;
	}

	public function plans(){
		return $query = self::where('status', 1);
	}

	public function add_stripe_plan($post_data, $action, $fields, $user, $account_token, $row_id=false){

		$stripe_model	= new StripeModel();

		if($action == 'save'){
			$package = $this->format((object) $post_data);
			$return = $stripe_model->add_plan($package);
			if($return['status'] == true){
				return ['stripe_price_id' => $return['id']];
			}

			die(json_encode(['status' => false, 'message' => $return['message']]));
		}else{

			$plan = $this->fetch_row_by_id($row_id);

			if($plan){
				$package 	= $this->format($plan);
				$return 	= $stripe_model->update_plan($package);
			
				if($return['status'] == true){
					return ['stripe_price_id' => $return['id']];
				}

				die(json_encode(['status' => false, 'message' => $return['message']]));
			}else{
				die(json_encode(['status' => false, 'message' => 'There was an error while processing your request!']));
			}
		}
	}
    
}
