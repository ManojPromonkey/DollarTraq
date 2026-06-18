<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShipmentsStopsModel extends CoreModel
{
    protected $table = 'shipment_stops';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($shipment = false){
		//dd($shipment);

		if($shipment){

			$shipment->added_on_formatted 	= Carbon::parse($shipment->added_on)->format('d M, Y');
			$shipment->stop_name = clean_display($shipment->stop_name);

			$shipment->stop_type_label = $this->key_filter($this->stop_types(), $shipment->stop_type);

			$shipment->stop_starting_at_date = '';
			$shipment->stop_starting_at_time = '';

			$shipment->stop_ending_at_date = '';
			$shipment->stop_ending_at_time = '';

			if($shipment->stop_starting_at != '0000-00-00 00:00:00'){
				$shipment->stop_starting_at_date 	= Carbon::parse($shipment->stop_starting_at)->format('Y-m-d');
				$shipment->stop_starting_at_time 	= Carbon::parse($shipment->stop_starting_at)->format('H:i:s');
			}

			if($shipment->stop_ending_at != '0000-00-00 00:00:00'){
				$shipment->stop_ending_at_date 	= Carbon::parse($shipment->stop_ending_at)->format('Y-m-d');
				$shipment->stop_ending_at_time 	= Carbon::parse($shipment->stop_ending_at)->format('H:i:s');
			}

			$_coordinates = ['lat' => '', 'lng' => '', 'latitude' => '', 'longitude' => ''];

			if($shipment->coordinates != ''){
				$coordinates = @json_decode(clean_display($shipment->coordinates), true);

				if(is_array($coordinates)){

					if(array_key_exists('lat', $coordinates)){
						$_coordinates['lat'] = (float)clean_display(trim($coordinates['lat']));
						$_coordinates['latitude'] = (float)clean_display(trim($coordinates['lat']));
					}

					if(array_key_exists('lng', $coordinates)){
						$_coordinates['lng'] = (float)clean_display(trim($coordinates['lng']));
						$_coordinates['longitude'] = (float)clean_display(trim($coordinates['lat']));
					}
				}
			}

			$shipment->_coordinates = $_coordinates;
		}

		return $shipment;
	}


    public function shipment_stops($row_id = false){
		$_stops = [];

		if($row_id){
		  $stops = self::where('shipment_id', trim($row_id));
			if($stops->count() > 0){
				$stops = $stops->get();
				foreach($stops as $stop){
					$_stops[] = $this->format($stop);
				}
			}
		}

		return $_stops;
	}


    public function stop_types(){

		$list = [];

		$list[] = ['key' => 'pickup', 'value' => 'Pickup'];
		$list[] = ['key' => 'drop_off', 'value' => 'Drop Off'];

		return $list;
	}

    public function shipment_stop_save_before($post = [], $action = '', $fields = [], $user = false, $account_token = ''){

		$sort_order = 1;

		if($action == 'save'){
		
			if(isset($post['shipment_id'])){

				$shipment_id = $post['shipment_id'];

				if($shipment_id != ''){
					$max_sort_order = self::orderByDesc('sort_order');
					if($max_sort_order->count() > 0){
						$max_sort_order = $max_sort_order->first();
						$sort_order = $max_sort_order->sort_order + 1;
					}
				}
			}

			return ['sort_order' => $sort_order];
		}

		if($action == 'update'){

			return [
				'stop_starting_at' => $post['stop_starting_at_date'] . " " . $post['stop_starting_at_time'],
				'stop_ending_at' => $post['stop_ending_at_date'] . " " . $post['stop_ending_at_time']
			];
		}

		return [];
	}
    
}
