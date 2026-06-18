<?php

namespace App\Models\Tracking;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\CMS\CMSEmailModel;

class ShipmentTrackingGoogleMap extends CoreModel
{
    protected $table = 'queries';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function map_key(){
		return 'AIzaSyAh26m8Ce0wsHBi8RTh3B6oC00sUz43WKU';
	}

	public function address_to_coords($address = false){

		$api_key = $this->map_key();

		$coords_request = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $api_key;

		$response = file_get_contents($coords_request);
		$json = json_decode($response, TRUE);

		if($json['status'] == 'OK'){
			return $json['results'][0]['geometry']['location'];
		}

		return false;
	}
    
}
