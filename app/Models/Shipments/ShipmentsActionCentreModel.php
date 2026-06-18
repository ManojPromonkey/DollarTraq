<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use App\Models\Shipments\ShipmentsModel;
use App\Models\Shipments\ShipmentsUpdatesModel;
use App\Models\Shipments\ShipmentsCarriersModel;
use App\Models\Shipments\ShipmentsTrackingMethods;
use App\Models\Shipments\ShipmentsStopsModel;

use App\Models\Shipments\ShipmentsTrackingMethodsModel;

use App\Models\Drivers\DriversModel;
use App\Models\Customers\CustomersModel;
use App\Models\Chats\ChatsModel;
use App\Models\SMS\SMSModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class ShipmentsActionCentreModel extends CoreModel{
    const APP_STATUS_INIT = 0;
	const APP_STATUS_NOT_INSTALLED = 1;

	const REQEUST_SENT_PENDING = 0;
	const REQEUST_SENT_DONE = 1;

    protected $table = 'action_center_requests';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    	public function shipment(){
        return $this->belongsTo(ShipmentsModel::class, 'shipment_id', 'row_id');
	}

	public function list($auth){
		$filters = request()->input('filters');
		$filters = !empty($filters) ? json_decode($filters, true) : [];

		$listing = ShipmentsUpdatesModel::with([
				'shipment.carrier',
				'shipment.trackingMethod'
			])
			->whereHas('shipment', function ($q) {
				$q->whereIn('status', [0, 1, 2, 3]);
			});

		// Apply filters dynamically
		if (is_array($filters)) {
			foreach ($filters as $key => $value) {

				if ($value === '**' || $value === null || $value === '') {
					continue;
				}

				$listing->whereHas('shipment', function ($q) use ($key, $value) {
					$q->where($key, $value);
				});
			}
		}

     	$listing->distinct();

	   	$perPage = request()->input('per_page', 10);
	   	$paginator = $listing->paginate($perPage);

		$data = $paginator->getCollection()->map(function ($item) {

		return [
			'shipment_number'        => $item->shipment->shipment_number ?? null,
			'shipment_row_id'        => $item->shipment->row_id ?? null,
			'tracking_full_number'   => $item->shipment->tracking_full_number ?? null,
			'shipment_added_on'      => $item->shipment->added_on ?? null,
			'shipment_driver'        => $item->shipment->driver ?? null,
			'status'                 => $item->shipment->status ?? null,

			'send_updates_to'        => $item->send_updates_to,
			'update_type'            => $item->update_type,
			'track_days'             => $item->track_days,

			'carrier_title'          => $item->shipment->carrier->title ?? null,
			'tracking_method_title'  => $item->shipment->trackingMethod->title ?? null,

			'shipment_date'          => !empty($item->shipment->added_on) ? Carbon::parse($item->shipment->added_on)->format('d M, Y'): null,

			'shipment_time'          => !empty($item->shipment->added_on) ? Carbon::parse($item->shipment->added_on)->format('h:i A'): null,
		];

		});

		return [
			'status' => true,
			'total'    => $paginator->total(),
			'page'     => $paginator->currentPage(),
			'per_page' => $paginator->perPage(),
			'records' => $data
		];
    }

	

	public function load_requests($driver_id = false){

		return self::with([
			'shipment:id,row_id,shipment_number,tracking_full_number,added_on,shippment_carrier,tracking_method,driver',
			'shipment.carrier:row_id,title',
			'shipment.trackingMethod:row_id,title'
		])
		->where('driver_id', $driver_id)
		->whereHas('shipment', function ($q) {
			$q->where('driver', '');
		});
	}

	public function format($row = false){

		if($row){
			$row->shipment_date 	= Carbon::parse($customer->shipment_added_on)->format('d M, Y');
			$row->shipment_time 	= Carbon::parse($customer->shipment_added_on)->format('H:i A');

			$row->status = (int)$row->status;
		}

		return $row;
	}

	public function driver_load_requests($driver_row_id = false){
        $shipments_stops_model  = new ShipmentsStopsModel();

		$_load_requests = [];

		$load_requests = $this->load_requests($driver_row_id);

		if($load_requests->count() > 0){

			$load_requests = $load_requests->get();

			$shipment_row_ids = [];

			foreach($load_requests as $load_request){

				$shipment_row_ids[] = $load_request->shipment_row_id;

				$load_request->pickups = [];
				$load_request->drop_offs = [];
			}

			$pickup_stops = [];
			$drop_off_stops = [];

			if(count($shipment_row_ids) > 0){

				$stoppages = ShipmentsStopsModel::whereIn('shipment_id', $shipment_row_ids);
				if($stoppages->count() > 0){

					$stoppages = $stoppages->get();

					foreach($stoppages as $stoppage){

						if($stoppage->stop_type == 'pickup'){
							$pickup_stops[$stoppage->shipment_id][] = $stoppage;
						}

						if($stoppage->stop_type == 'drop_off'){
							$drop_off_stops[$stoppage->shipment_id][] = $stoppage;
						}
					}
				}
			}

			foreach($load_requests as $load_request){

				if(array_key_exists($load_request->shipment_row_id, $pickup_stops)){
					$load_request->pickups = $pickup_stops[$load_request->shipment_row_id];
				}
				
				if(array_key_exists($load_request->shipment_row_id, $drop_off_stops)){
					$load_request->drop_offs = $drop_off_stops[$load_request->shipment_row_id];
				}

				$_load_requests[] = $this->format($load_request);
			}
		}

		return $_load_requests;
	}



	public function request_send($request, $user){
		$shipments_model 	= new ShipmentsModel();
		$drivers_model 	= new DriversModel();

		$shipments_stops_model 	= new ShipmentsStopsModel();
		$customers_model 		= new CustomersModel();

		$chats_model 			= new ChatsModel();
		$sms_model 			= new SMSModel();
		$shipment_tracking_google_map 		= new ShipmentsTrackingMethodsModel();

		$shipment_row_id  = $request->filled('shipment_row_id') ? $request->post('shipment_row_id') : '';
		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);

		if($shipment){

			$coords_defined = true;
			
			$pickup_defined = false;
			$dropoff_defined = false;

			$shipment_stops = $shipments_stops_model->shipment_stops($shipment_row_id);

			if(count($shipment_stops) > 0){

				foreach($shipment_stops as $shipment_stop){

					if($shipment_stop->stop_type == 'pickup' || $shipment_stop->stop_type == 'drop_off'){
					
						if(
							(!isset($shipment_stop->_coordinates) || !is_array($shipment_stop->_coordinates))
							||
							(
								is_array($shipment_stop->_coordinates)
								&&
								(
									(!array_key_exists('lat', $shipment_stop->_coordinates) || !array_key_exists('lng', $shipment_stop->_coordinates))
									||
									(
										isset($shipment_stop->_coordinates['lat']) && $shipment_stop->_coordinates['lat'] == ''
										||
										isset($shipment_stop->_coordinates['lng']) && $shipment_stop->_coordinates['lng'] == ''
									)
								)
							)
						){

							$address_string = $this->makeAddress($shipment_stop);

							$coords = $shipment_tracking_google_map->address_to_coords($address_string);

							if($coords !== false){

								$_coords = [
									'lat' => number_format((float)$coords['lat'], 8, '.', ''),
								'lng' => number_format((float)$coords['lng'], 8, '.', '')
								];

								$shipments_stops_model->set_post_data('coordinates', json_encode($_coords));
								$shipments_stops_model->set_post_data('updated_on', date('Y-m-d H:i:s'));

								$shipments_stops_model->post_update($shipment_stop->row_id);
							}
						}
					}
				}
			}

			$shipment_stops = $shipments_stops_model->shipment_stops($shipment_row_id);

			if(count($shipment_stops) > 0){

				foreach($shipment_stops as $shipment_stop){

					if($shipment_stop->stop_type == 'pickup'){
						
						$pickup_defined = true;
					}

					if($shipment_stop->stop_type == 'drop_off'){
						
						$dropoff_defined = true;
					}

					if($shipment_stop->stop_type == 'pickup' || $shipment_stop->stop_type == 'drop_off'){
					
						if(
							(!isset($shipment_stop->_coordinates) || !is_array($shipment_stop->_coordinates))
							||
							(
								is_array($shipment_stop->_coordinates)
								&&
								(
									(!array_key_exists('lat', $shipment_stop->_coordinates) || !array_key_exists('lng', $shipment_stop->_coordinates))
									||
									(
										isset($shipment_stop->_coordinates['lat']) && $shipment_stop->_coordinates['lat'] == ''
										||
										isset($shipment_stop->_coordinates['lng']) && $shipment_stop->_coordinates['lng'] == ''
									)
								)
							)
						){

							$coords_defined = false;
						}
					}
				}
			}

			if(!$coords_defined || (!$pickup_defined || !$dropoff_defined)){
				return ['status'=>false, 'message' => 'Pickup or Drop off location not defined or there coordinates not collected. Please edit the shipment and update the pickup and drop off locations.'];
			}else{

				/* SMS */
				$mobile = "+" . $shipment->tracking_cc . $shipment->tracking_full_number;
				$sms_model->app_install_sms($mobile);

				$driver = $drivers_model->fetch_row_by_field('mobile', $shipment->tracking_full_number);

				$app_installed = 0;
				$driver_id = '';

				if($driver){

					if($driver->app_installed == '1'){

						$app_installed = 1;
					}
					
					$driver_id = $driver->row_id;

					if($driver->token != ''){

						$response = send_expo_push(
							trim($driver->token),
							'Load Assigned',
							'A new load has been assigned to you 🚚',
							[
								'action' => 'reload',
								'page'    => 'homepage'
							]
						);
					}
				}

				/*
				Check if already entered
				*/
				$entry = $this->fetch_row_by_field('shipment_id', $shipment->row_id);

				if(!$entry){
				
					$row_id = $this->generate_unique_id();
					$this->set_post_data('row_id', $row_id);
					$this->set_post_data('shipment_id', $shipment->row_id);
					$this->set_post_data('contact_number', $shipment->tracking_full_number);
					$this->set_post_data('app_status', $app_installed);
					$this->set_post_data('driver_id', $driver_id);

					$this->post_save();
				}

				return ['status'=>true, 'message' => 'Request sent successfully.'];
			}
		}else{
			return ['status'=>true, 'message' => 'Request sent successfully.'];
		}
		
	}

	public function makeAddress($row){
		return clean_display($row->address) . ", " . clean_display($row->address_2) . ", " . clean_display($row->city) . ", " . clean_display($row->state) . " - " . clean_display($row->zipcode) . ", " . clean_display($row->country);
	}

    
}
