<?php

namespace App\Models\Settings;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SettingsModel extends CoreModel
{

    protected $table = 'settings';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($row = false){
		if($row){
			$row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M Y');
			$row->value = clean_display($customer->value);
		}

		return $row;
	}

	public function get_by_id($id=false){
		if($id){
			return self::where('id', trim($id))->first();  
		}
		return false;
	}
	
	public function get_by_key($key=false){
		if($key){
			$query = self::where('key', trim($key))->first();
			
			//if($query->count() > 0){
				return $query;
			//}	
		}
		return false;
	}
	
	public function get_value_by_key($key=false){
		if($key){
			$data = $this->get_by_key($key);
			if($data){
				return $data->value;
			}	
		}
		return false;
	}
	
	




    
}
