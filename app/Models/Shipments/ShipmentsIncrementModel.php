<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShipmentsIncrementModel extends CoreModel{
    const TYPE_SHIPMENT = 'shipment';
    

    protected $table = 'shipment_increments';
    public $timestamps = false;

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($row = false){

       if($row){
            $row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');
			$row->title = clean_display($row->title);
		}

        return $row;
    }

    public function update_increment($type){
		$row = $this->last_increment($type);

		if($row !== false){
			$increment = $row->increment + 1;

            self::where('type', $type)
            ->update([
                'increment' => $increment,
                'updated_on' => now(),
            ]);
        }
	}


    public function last_increment($type){
        return self::where('type', $type)->orderByDesc('id')->first() ?: false;
    }

    
}
