<?php

namespace App\Models\Customers;

use App\Core\CoreModel;

use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class CustomersSubscriptionsHistoryModel extends CoreModel{
	const PAYMENT_PENDING = 0;
	const PAYMENT_PAID = 1;

	const ACTION_SUBSCRIPTION_ENTRY = 'E';
	const ACTION_SUBSCRIPTION_CANCELLATION = 'C';
	const ACTION_SUBSCRIPTION_SWITCH = 'S';

    	protected $table = 'customers_subscriptions_history';
	public $timestamps = false;


    	function __construct(){
        $this->setTableIndex('row_id');
	}

    	public function format($row = false){
		if($row){
			$row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');
		}
		return $row;
	}


	
}