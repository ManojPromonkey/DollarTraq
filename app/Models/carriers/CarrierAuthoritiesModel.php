<?php

namespace App\Models\Carriers;

use App\Modules\Base\Models\BaseModel;

class CarrierAuthoritiesModel extends BaseModel
{
	
	protected $table = 'carrier_authorities';
	public $timestamps = false;

	function __construct(){
	
		$this->setTableIndex('row_id');
	}

	public function format($carrier = false){

		if($carrier){

			$carrier->docket_number = clean_display($carrier->docket_number);
		}

		return $carrier;
	}
}