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


class CustomersUsersRolesModel extends CoreModel{

    	protected $table = 'customers_users_roles';
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