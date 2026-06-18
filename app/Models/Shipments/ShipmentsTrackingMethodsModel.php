<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShipmentsTrackingMethodsModel extends CoreModel
{
    protected $table = 'shipment_tracking_methods';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($row = false){

       if($row){
            $row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M Y');

			$row->title = clean_display($row->title);
		}

        return $row;
    }


    
}
