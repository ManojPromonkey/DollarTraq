<?php

namespace App\Models\Motive;

use App\Core\CoreModel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

//use App\Models\Customers\CustomersModel;

class MotiveVehicle extends CoreModel
{
    	const STATUS_NEW = '0';
	const STATUS_PROCESSED = '1';
	const STATUS_CONVERTED = '2';


    	protected $table = 'motive_vehicles';

	protected $fillable = ['motive_id', 'number', 'make', 'model', 'raw_data'];

    	protected $casts = [
        'raw_data' => 'array'
    	];

    	function __construct(){
        $this->setTableIndex('row_id');
	}

    	public function format($row = false){
		if($row){
			$row->created_at_formatted = date("d M Y", strtotime($row->created_at));
		}
		return $row;
	}



	public function statuses(){

		$status = [];
		$status[] = ['key' => self::STATUS_NEW, 'value' => 'New'];
		$status[] = ['key' => self::STATUS_PROCESSED, 'value' => 'Processing'];
		$status[] = ['key' => self::STATUS_CONVERTED, 'value' => 'Converted'];
		
		return $status;
	}


	
    
}
