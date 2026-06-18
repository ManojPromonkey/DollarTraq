<?php

namespace App\Models\Tracking;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

//use Carbon\Carbon;

use App\Models\Shipments\ShipmentsModel;
use App\Models\Shipments\ShipmentsStopsModel;
use App\Models\Drivers\DriversModel;
use App\Models\Customers\CustomersModel;
use App\Models\CMS\CMSEmailModel;

use Illuminate\Support\Carbon;


class ShipmentTrackingPulseModel extends CoreModel
{
    	const TYPE_ARRIVED_PICKUP_LOCATION = 'arrived';
	const TYPE_SHIPMENT_STARTED = 'started';
	const TYPE_SHIPMENT_IN_TRANSIT = 'transit';
	const TYPE_SHIPMENT_DELIVERED = 'delivered';
	const TYPE_SHIPMENT_DELIVERED_EXITED = 'destination_left';  

    protected $table = 'shipment_tracking_pulses';

    function __construct(){
        $this->setTableIndex('row_id');
	}

	public function format($row = false){
		//$row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M Y');
		return $row;
	}
    
	public function shipment_tracks($shipment_id = false){

		$_pulses = [];

		if($shipment_id){
			$pulses = self::where('shipment_id', trim($shipment_id))->groupBy('tracking_type')->orderBy('added_on', 'asc');

			if($pulses->count() > 0){
				$pulses = $pulses->get();
				foreach($pulses as $pulse){
					$_pulses[$pulse->tracking_type] = $pulse;
				}
			}
		}

		return $_pulses;
	}

	public function tracking_pulses($shipment_id = false, $last_pulse = ''){

		$_pulses = new stdClass;

		$_pulses->arrived = [];
		$_pulses->started = [];
		$_pulses->transits = [];

		$coords = [];

		if($shipment_id){

			$pulses = self::where('shipment_id', trim($shipment_id))->orderBy('added_on', 'asc');
			if($last_pulse && $last_pulse != ''){
				$pulses = $pulses->where('id', '>', $last_pulse);
			}


			if($pulses->count() > 0){

				$pulses = $pulses->get();

				foreach($pulses as $pulse){

					if($pulse->lat != '' && $pulse->long != ''){
					
						$coords[] = [
							'lat' => number_format((float)$pulse->lat, 8, '.', ''),
    						'lng' => number_format((float)$pulse->long, 8, '.', ''),
							'date' => date("d M Y h:i A", strtotime($pulse->added_on))
						];
					}

					if($pulse->tracking_type == 'arrived'){

						$_pulses->arrived = $pulse;
					}

					if($pulse->tracking_type == 'started'){

						$_pulses->started = $pulse;
					}

					if($pulse->tracking_type == 'transit'){

						$_pulses->transits[] = $pulse;
					}
				}
			}
		}

		return [$coords, $_pulses];
	}


	public function drivers_tracking_pulse($request, $user){

		$shipment_row_id  	= $request->filled('shipment_row_id') ? $request->post('shipment_row_id') : '';
		$row_data		  	= $request->filled('row_data') ? $request->post('row_data') : '';
		
		$shipments_model		= new ShipmentsModel();
		$shipments_stops_model	= new ShipmentsStopsModel();
		$drivers_model			= new DriversModel();
		$customers_model		= new CustomersModel();

		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);
		if($shipment){

			$row_data = @json_decode($row_data, true);

			if(is_array($row_data)){

				if(count($row_data) > 30){
					file_put_contents("file_" . $shipment_row_id . "_" . $user['row_id'] . ".txt", json_encode($row_data));

					return ['status' => true];
				}else{

					foreach($row_data as $data_row){

						$lat = $data_row['lat'];
						$long = $data_row['long'];
						$speed = $data_row['speed'];

						$timestamp = $data_row['timestamp'];

						$device = $data_row['device'];
						$device_data = $data_row['device_data'];

						$_device_data = [];

						if($device_data){

							if(!is_array($device_data)){
								$device_data = @json_decode(trim($device_data), true);
							}

							if(is_array($device_data)){

								foreach($device_data as $device_data_key => $device_data_value){
									$_device_data[trim($device_data_key)] = trim($device_data_value);
								}
							}
						}

						$row_id = $this->generate_unique_id();

						$this->set_post_data('row_id', $row_id);
						$this->set_post_data('shipment_id', $shipment->row_id);
						$this->set_post_data('driver_id', $user['row_id']);
						$this->set_post_data('lat', $lat);
						$this->set_post_data('long', $long);
						$this->set_post_data('speed', $speed);
						$this->set_post_data('tracking_type', self::TYPE_SHIPMENT_IN_TRANSIT);
						$this->set_post_data('device', $device);
						$this->set_post_data('device_data', json_encode($_device_data));
						$this->set_post_data('status', 1);

						if($timestamp && $timestamp != ''){

							$date = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp);

							if($date->format('Y-m-d H:i:s') === $timestamp) {

								$this->set_post_data('added_on', $date->toDateTimeString());
							}else{
								$this->set_post_data('added_on', date('Y-m-d H:i:s'));
							}
						}else{
							$this->set_post_data('added_on', date('Y-m-d H:i:s'));
						}

						$this->post_save();
					}

					return['status' => true];
				}
			}else{
				return ['status' => false];
			}
			
		}else{

			$this->response(['status'=>false, 'message' => 'Information missing.']);
		}

	}



	public function drivers_tracking_update($request, $user){

		$shipment_row_id  	= $request->filled('shipment_row_id') ? $request->post('shipment_row_id') : '';
		$lat			  	= $request->filled('lat') ? $request->post('lat') : '';
		$long			= $request->filled('long') ? $request->post('long') : '';
		$device			= $request->filled('device') ? $request->post('device') : '';
		$device_data		= $request->filled('device_data') ? $request->post('device_data') : '';
		
		$shipments_model		= new ShipmentsModel();
		$shipments_stops_model	= new ShipmentsStopsModel();
		$drivers_model			= new DriversModel();
		$customers_model		= new CustomersModel();

		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);
		if($shipment){
			$tracking_type = '';
			$updated_status = '';
			$status_time = '';
			$message = 'Status updated successfully';

			if($shipment->status == $shipments_model::STATUS_PENDING){

				/*
				Start Shipment
				*/
				$tracking_type = self::TYPE_ARRIVED_PICKUP_LOCATION;

				$shipments_model->set_post_data('status', $shipments_model::STATUS_AWAITING_PICKUP);
				$shipments_model->set_post_data('updated_on', date('Y-m-d H:i:s'));

				$shipments_model->post_update($shipment->row_id);

				$updated_status = 'Arrived At Location - Awaiting Pickup';
				$status_time = date("d M Y, h:i A", strtotime(date('Y-m-d H:i:s')));

				$message = 'Status updated - Arrived At Location - Awaiting Pickup';
			}

			if($shipment->status == $shipments_model::STATUS_AWAITING_PICKUP){

				/*
				Start Shipment
				*/
				$tracking_type = self::TYPE_SHIPMENT_STARTED;

				$shipments_model->set_post_data('status', $shipments_model::STATUS_IN_TRANSIT);
				$shipments_model->set_post_data('updated_on', date('Y-m-d H:i:s'));

				$shipments_model->post_update($shipment->row_id);

				$updated_status = 'In Transit - Shipment Started';
				$status_time = date("d M Y, h:i A", strtotime(date('Y-m-d H:i:s')));

				$message = 'Status updated - In Transit - Shipment Started';
			}

			if($shipment->status == $shipments_model::STATUS_IN_TRANSIT){
				/*
				Start Shipment
				*/
				$tracking_type = self::TYPE_SHIPMENT_DELIVERED;

				$shipments_model->set_post_data('status', $shipments_model::STATUS_DELIVERED);
				$shipments_model->set_post_data('updated_on', date('Y-m-d H:i:s'));

				$shipments_model->post_update($shipment->row_id);

				$updated_status = 'Shipment Delivered';
				$status_time = date("d M Y, h:i A", strtotime(date('Y-m-d H:i:s')));

				$message = 'Status updated - Shipment Delivered';
			}

			$_device_data = [];

			if($device_data){

				if(!is_array($device_data)){

					$device_data = @json_decode(trim($device_data), true);
				}

				if(is_array($device_data)){

					foreach($device_data as $device_data_key => $device_data_value){

						$_device_data[trim($device_data_key)] = trim($device_data_value);
					}
				}
			}

			$row_id = $this->generate_unique_id();

			$this->set_post_data('row_id', $row_id);
			$this->set_post_data('shipment_id', $shipment->row_id);
			$this->set_post_data('driver_id', $user['row_id']);
			$this->set_post_data('lat', $lat);
			$this->set_post_data('long', $long);
			$this->set_post_data('tracking_type', $tracking_type);
			$this->set_post_data('device', $device);
			$this->set_post_data('device_data', json_encode($_device_data));
			$this->set_post_data('added_on', date('Y-m-d H:i:s'));
			$this->set_post_data('status', 1);

			$this->post_save();

			/*
			Load shipment
			*/
			$shipment = $shipments_model->fetch_row_by_id($shipment->row_id);

			$stoppages = ShipmentsStopsModel::where('shipment_id', $shipment->row_id);

			$pickup_stops = [];
			$drop_off_stops = [];

			if($stoppages->count() > 0){

				$stoppages = $stoppages->get();

				foreach($stoppages as $stoppage){

					if($stoppage->stop_type == 'pickup'){

						$pickup_stops[] = $shipments_stops_model->format($stoppage);
					}

					if($stoppage->stop_type == 'drop_off'){

						$drop_off_stops[] = $shipments_stops_model->format($stoppage);
					}
				}
			}

			$shipment->pickups = $pickup_stops;
			$shipment->drop_offs = $drop_off_stops;

			$shipment = $shipments_model->format($shipment);

			$shipment_timeline = $shipments_model->shipment_timeline($shipment);

			$customer = $customers_model->fetch_row_by_id($shipment->customer);

			if($customer){

				/*
				Send email
				*/
				$vars = [];
				$vars['customer_name'] = ucwords(clean_display($customer->first_name) . ' ' . clean_display($customer->last_name));
				$vars['sitename'] = sitename();
				$vars['shipment_number'] = clean_display($shipment->shipment_number);
				$vars['status'] = clean_display($updated_status);
				$vars['time'] = $status_time;

				$cms_email_model = new CMSEmailModel();

				$cms_email_model->send_template_email('shipment_updates', $vars, $customer->email, ucwords(clean_display($customer->first_name) . ' ' . clean_display($customer->last_name)));

				/*
				CC Email
				*/
				$email_updates_to = $shipment->email_updates_to;

				if($email_updates_to != ''){

					$email_updates_to = @explode(',', clean_display($email_updates_to));

					if(is_array($email_updates_to)){

						foreach($email_updates_to as $email_update_to){

							if($email_update_to != ''){
							
								$cms_email_model->send_template_email('shipment_updates', $vars, trim($email_update_to), "User");
							}
						}
					}
				}
			}

			return ['status' => true, 'shipment_timeline' => array_values($shipment_timeline), 'shipment' => $shipment, 'message' => $message];
		}else{
			return ['status'=>false, 'message' => 'Information missing.'];
		}

	}
    
}
