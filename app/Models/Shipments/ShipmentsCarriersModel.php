<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShipmentsCarriersModel extends CoreModel
{
    protected $table = 'shipment_carriers';
    const STATUS_ACTIVE = 1;

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($row = false){

        if($row){
            $row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');

			$row->person_name   = ucwords(clean_display($row->person_name));
			$row->title         = ucwords(clean_display($row->title));
			$row->email         = clean_display($row->email);
        }

        return $row;
    }


    
}
