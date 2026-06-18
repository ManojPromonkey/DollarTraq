<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use App\Models\Shipments\ShipmentsIncrementModel;
use App\Models\Shipments\ShipmentsUpdatesModel;
use App\Models\Shipments\ShipmentsActionCentreModel;
use App\Models\Shipments\ShipmentsCarriersModel;
use App\Models\Shipments\ShipmentsTrackingMethodsModel;
use App\Models\Shipments\ShipmentsDocuments;
use App\Models\Shipments\ShipmentsStopsModel;

use App\Models\Tracking\ShipmentTrackingPulseModel;

use App\Models\Tracking\ShipmentTrackingGoogleMap;

use App\Models\Drivers\DriversModel;


use App\Models\Customers\CustomersModel;
use App\Models\CMS\CMSEmailModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

///
class ShipmentsModel extends CoreModel
{
    protected $table = 'shipments';

    	const STATUS_PENDING = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_AWAITING_PICKUP = 2;
	const STATUS_IN_TRANSIT = 3;
	const STATUS_DELIVERED = 4;

	const TRACKING_STATUS_RAI = 0; // Requesting app install
	const TRACKING_STATUS_EWI = 1; // Expired without installation
	const TRACKING_STATUS_TC = 2; // Tracking completed

    	function __construct(){
        	$this->setTableIndex('row_id');
	}

	public function carrier(){
     	return $this->belongsTo(ShipmentsCarriersModel::class, 'shippment_carrier', 'row_id');
    	}

    	public function trackingMethod(){
		return $this->belongsTo(ShipmentsTrackingMethodsModel::class, 'tracking_method', 'row_id');
    	}

	public function stops(){
		return $this->hasMany(ShipmentsStopsModel::class, 'shipment_id', 'row_id');
	}



    public function format($shipment = false){

		if($shipment){

			$shipment->added_on_formatted 	= Carbon::parse($shipment->added_on)->format('d M, Y');

			$shipment->notes = clean_display($shipment->notes);
			$shipment->email_updates_to = clean_display($shipment->email_updates_to);

			$shipment->shipment_date_formatted = '';

			if(isset($shipment->shipment_date)){
				$shipment->shipment_date_formatted 	= Carbon::parse($shipment->shipment_date)->format('d M, Y');
			}

			if($shipment->tracking_start_at != '0000-00-00 00:00:00'){
				$shipment->tracking_start_at_date 	= Carbon::parse($shipment->tracking_start_at)->format('Y-m-d');
				$shipment->tracking_start_at_time 	= Carbon::parse($shipment->tracking_start_at)->format('H:i:s');
			}

			$shipment->shippment_carrier_label = '';
			if(isset($shipment->carrier_title)){
				$shipment->shippment_carrier_label = ucwords(clean_display($shipment->carrier_title));
			}
			
			$shipment->tracking_method_label = '';
			if(isset($shipment->tracking_title)){
				$shipment->tracking_method_label = clean_display($shipment->tracking_title);
			}

			/* Load updates*/
			$_updates = [];

            	$shipments_updates_model	= new shipmentsUpdatesModel();

			$updates = ShipmentsUpdatesModel::where('shipment_id', $shipment->row_id);

			if($updates->count() > 0){
				$updates = $updates->get();
				foreach($updates as $update){
					$_updates[] = $shipments_updates_model->format($update);
				}
			}

			$shipment->updates = $_updates;

			$statuses = $this->status();

			$shipment->status_label = $this->key_filter($statuses, $shipment->status);

			$shipment->status = (int)$shipment->status;
			// $shipment->driver_photo_url = '';
			
			// if($shipment->driver_photo != ''){

			// 	$shipment->driver_photo_url = media_url() . 'uploads/drivers/' . clean_display($shipment->driver_photo);
			// }

		}

		return $shipment;
	}


    public function control_tower_listing(){

		$listing = self::with(['carrier', 'trackingMethod'])->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_AWAITING_PICKUP, self::STATUS_IN_TRANSIT]);

		$listing->distinct();

	   	$perPage = request()->input('per_page', 10);
	   	$paginator = $listing->paginate($perPage);

		$data = $paginator->getCollection()->map(function ($item) {
			$shipments_updates_model	= new shipmentsUpdatesModel();
			$statuses = $this->status();
			/* Load updates*/
			$_updates = [];
			$updates = ShipmentsUpdatesModel::where('shipment_id', $item->row_id);

			if($updates->count() > 0){
				$updates = $updates->get();
				foreach($updates as $update){
					$_updates[] = $shipments_updates_model->format($update);
				}
			}

			$status_label = $this->key_filter($statuses, $item->status);

			$status = (int)$item->status;

			return [
				'id'        			=> $item->id,
				'row_id'        		=> $item->row_id,
				'shipment_number'  	     => $item->shipment_number,
				'shippment_carrier'      => $item->shippment_carrier,
				'tracking_method'        => $item->tracking_method,
				'tracking_cc'   		=> $item->tracking_cc,
				'tracking_full_number'   => $item->tracking_full_number,

				'tracking_start_at'      => $item->tracking_start_at,
				'tracking_timezone'      => $item->tracking_timezone,
				'email_updates_to'     	=> $item->email_updates_to,
				'notes'     	  		=> $item->notes,
				'customer'        		=> $item->customer,
				'driver'   			=> $item->driver,
				'last_email'    		=> $item->last_email,

				'added_on'        		=> $item->added_on,
				'updated_on'   		=> $item->updated_on,
				'status_label'     		=> $status_label,
				'status'     			=> $status,

				'tracking_title'        	=> $item->trackingMethod->title ?? null,
				'carrier_title'       	=> $item->carrier->title ?? null,


				'added_on_formatted' 	=> Carbon::parse($item->added_on)->format('d M, Y'),
				'shipment_date'        	=> Carbon::parse($item->shipment_date)->format('d M, Y') ?? null,
				'shipment_date_formatted' 		=> Carbon::parse($item->shipment_date)->format('d M, Y') ?? '',
				'tracking_start_at_date'        	=> Carbon::parse($item->tracking_start_at)->format('Y-m-d') ?? null,
				'tracking_start_at_time'        	=> Carbon::parse($item->tracking_start_at)->format('H:i:s') ?? null,
				'shippment_carrier_label'        	=> ucwords(clean_display($item->carrier_title)) ?? '',
				'tracking_method_label'        	=> clean_display($item->tracking_title) ?? '',

				'updates'        				=> $_updates,

				//'shipment_date'          => !empty($item->shipment->added_on) ? Carbon::parse($item->shipment->added_on)->format('d M, Y'): null,

				//'shipment_time'          => !empty($item->shipment->added_on) ? Carbon::parse($item->shipment->added_on)->format('h:i A'): null,
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


	public function load_search_listing(){
		$listing = self::with([
			'carrier:row_id,title',
			'trackingMethod:row_id,title'
		]);

		$filters = request()->input('filters');

		if (!empty($filters)) {

			$filters = is_array($filters)
				? $filters
				: json_decode($filters, true);

			if (is_array($filters)) {

				foreach ($filters as $filter_key => $filter_value) {

					if ($filter_value === '**' || $filter_value === '') {
						continue;
					}

					if ($filter_key === 'status') {
						$listing->where('status', $filter_value);
					} else {
						$listing->where($filter_key, $filter_value);
					}
				}
			}
		}

		$listing->distinct();

		$perPage = request()->input('per_page', 10);
	   	$paginator = $listing->paginate($perPage);

		$data = $paginator->getCollection()->map(function ($item) {
			$shipments_updates_model	= new shipmentsUpdatesModel();
			$statuses = $this->status();
			/* Load updates*/
			$_updates = [];
			$updates = ShipmentsUpdatesModel::where('shipment_id', $item->row_id);

			if($updates->count() > 0){
				$updates = $updates->get();
				foreach($updates as $update){
					$_updates[] = $shipments_updates_model->format($update);
				}
			}

			$status_label = $this->key_filter($statuses, $item->status);

			$status = (int)$item->status;

			return [
				'id'        			=> $item->id,
				'row_id'        		=> $item->row_id,
				'shipment_number'  	     => $item->shipment_number,
				'shippment_carrier'      => $item->shippment_carrier,
				'tracking_method'        => $item->tracking_method,
				'tracking_cc'   		=> $item->tracking_cc,
				'tracking_full_number'   => $item->tracking_full_number,

				'tracking_start_at'      => $item->tracking_start_at,
				'tracking_timezone'      => $item->tracking_timezone,
				'email_updates_to'     	=> $item->email_updates_to,
				'notes'     	  		=> $item->notes,
				'customer'        		=> $item->customer,
				'driver'   			=> $item->driver,
				'last_email'    		=> $item->last_email,

				'added_on'        		=> $item->added_on,
				'updated_on'   		=> $item->updated_on,
				'status_label'     		=> $status_label,
				'status'     			=> $status,

				'tracking_title'        	=> $item->trackingMethod->title ?? null,
				'carrier_title'       	=> $item->carrier->title ?? null,

				'added_on_formatted' 	=> Carbon::parse($item->added_on)->format('d M, Y'),
				'shipment_date'        	=> Carbon::parse($item->shipment_date)->format('d M, Y') ?? null,
				'shipment_date_formatted' 		=> Carbon::parse($item->shipment_date)->format('d M, Y') ?? '',
				'tracking_start_at_date'        	=> Carbon::parse($item->tracking_start_at)->format('Y-m-d') ?? null,
				'tracking_start_at_time'        	=> Carbon::parse($item->tracking_start_at)->format('H:i:s') ?? null,
				'shippment_carrier_label'        	=> ucwords(clean_display($item->carrier_title)) ?? '',
				'tracking_method_label'        	=> clean_display($item->tracking_title) ?? '',

				'updates'        				=> $_updates,

				//'shipment_date'          => !empty($item->shipment->added_on) ? Carbon::parse($item->shipment->added_on)->format('d M, Y'): null,

				//'shipment_time'          => !empty($item->shipment->added_on) ? Carbon::parse($item->shipment->added_on)->format('h:i A'): null,
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

	public function load_shipment($request, $user){
		$row_id = $request->filled('row_id') ? $request->post('row_id'): '';

		if (!$row_id) {
			return [
				'status' => false,
				'message' => 'Shipment not found.'
			];
		}

		$shipment = self::with([
				'carrier:row_id,title',
				'trackingMethod:row_id,title'
			])->where('row_id', $row_id)->first();

		if (!$shipment) {
			return [
				'status' => false,
				'message' => 'Shipment not found.'
			];
		}

		$shipment->tracking_title = $shipment->trackingMethod->title ?? null;
		$shipment->carrier_title = $shipment->carrier->title ?? null;

		return [
			'status' => true,
			'data' => $this->format($shipment),
		];
	}

    public function shipment_save_before($post = [], $action = '', $fields = [], $user = false, $account_token = ''){

		if($user->lifetime_loads == 0 || ($user->lifetime_loads - $user->consumed_loads) == 0){
			return ['shipments_count' => false];
		}

		return [
			'tracking_start_at' => $post['tracking_start_at_date'] . " " . $post['tracking_start_at_time'],
			'customer' => $user->row_id
		];
	}

	public function shipment_save_after($request, $post_data = [], $row_id = '', $action = '', $user = false){

        $customers_model     = new CustomersModel();

		if($action == 'save'){

			$shipment_number = $this->create_shipment_number();
			$this->set_post_data('shipment_number', $shipment_number);
			$this->post_update($row_id);

			/* Update shipment increment */
            	$shipments_increment_model     = new ShipmentsIncrementModel();
			$shipments_increment_model->update_increment($shipments_increment_model::TYPE_SHIPMENT);

			/* Load shipment */
			$shipments_tracking_methods     = new ShipmentsTrackingMethodsModel();
			$shipments_carriers             = new ShipmentsCarriersModel();

			$shipments_tracking_table       = $shipments_tracking_methods->getTable();
			$shipments_carriers_table       = $shipments_carriers->getTable();

			$shipment_row = self::with([
				'carrier:row_id,title',
				'trackingMethod:row_id,title'
			])->where('row_id', $row_id)->first();

			/* Reduce consumed loads  */
			$customers_model->set_post_data('consumed_loads', $user->consumed_loads + 1);
			$customers_model->set_post_data('lifetime_loads', $user->lifetime_loads - 1);
			$customers_model->post_update($user->row_id);

			$customer = $customers_model->fetch_row_by_id($shipment_row->customer);

			if($customer){
				/* Send email */ ///??
				$vars = [];
				$vars['customer_name'] = ucwords(clean_display($customer->first_name) . ' ' . clean_display($customer->last_name));
				$vars['sitename'] = 'DollarTraq';//sitename();///??
				$vars['shipment_number'] = clean_display($shipment_number);
				$vars['shipment_carrier'] = clean_display($shipment_row->carrier->title);
				$vars['tracking_method'] = clean_display($shipment_row->trackingMethod->title);
				$vars['application_url'] = application_url();///??

                	$cms_email_model     = new CMSEmailModel();
				$cms_email_model->send_template_email('new_shipment_email', $vars, $customer->email, ucwords(clean_display($customer->first_name) . ' ' . clean_display($customer->last_name)));

				/* CC Email */
				$email_updates_to = $shipment_row->email_updates_to;

				if($email_updates_to != ''){

					$email_updates_to = @explode(',', clean_display($email_updates_to));

					if(is_array($email_updates_to)){

						foreach($email_updates_to as $email_update_to){

							if($email_update_to != ''){
								$cms_email_model->send_template_email('new_shipment_email', $vars, trim($email_update_to), "User");
							}
						}
					}
				}
			}
		}

		if(array_key_exists('updates_items', $_POST)){

			$updates_items = @json_decode($_POST['updates_items'], true);

			if(is_array($updates_items) && count($updates_items) > 0){
                	$shipments_updates_model       = new shipmentsUpdatesModel();

				foreach($updates_items as $updates_item){

					$shipments_updates_model->set_post_data('shipment_id', $row_id);
					$shipments_updates_model->set_post_data('send_updates_to', $updates_item['send_updates_to']);
					$shipments_updates_model->set_post_data('update_type', $updates_item['update_type']);
					$shipments_updates_model->set_post_data('track_days', $updates_item['track_days']);
					$shipments_updates_model->set_post_data('track_time', $updates_item['track_time']);

					if(array_key_exists('row_id', $updates_item) && $updates_item['row_id'] != ''){

						$shipments_updates_model->set_post_data('updated_on', date('Y-m-d H:i:s'));

						$shipments_updates_model->post_update($updates_item['row_id']);
					}else{

						$_item_row_id = $shipments_updates_model->generate_unique_id('shipments_updates_model');

						$shipments_updates_model->set_post_data('row_id', $_item_row_id);
						$shipments_updates_model->set_post_data('added_on', date('Y-m-d H:i:s'));

						$shipments_updates_model->post_save();
					}
				}
			}
		}
	}

	public function status(){

		$statuses = [];

		$statuses[] = ['key' => self::STATUS_PENDING, 'value' => 'Pending Shipment'];
		$statuses[] = ['key' => self::STATUS_ACTIVE, 'value' => 'Active Shipment'];
		$statuses[] = ['key' => self::STATUS_AWAITING_PICKUP, 'value' => 'Awaiting Pickup'];
		$statuses[] = ['key' => self::STATUS_IN_TRANSIT, 'value' => 'In Transit'];
		$statuses[] = ['key' => self::STATUS_DELIVERED, 'value' => 'Delivered'];

		return $statuses;
	}

	public function status_colors(){

		$statuses = [];

		$statuses[self::STATUS_PENDING] = 'primary';
		$statuses[self::STATUS_ACTIVE] = 'info';
		$statuses[self::STATUS_AWAITING_PICKUP] = 'warning';
		$statuses[self::STATUS_IN_TRANSIT] = 'error';
		$statuses[self::STATUS_DELIVERED] = 'success';

		return $statuses;
	}

	public function tracking_status($key = ''){

		$progress = [];

		$progress[self::TRACKING_STATUS_RAI] = ['key' => self::TRACKING_STATUS_RAI, 'value' => 'Requesting App Install'];
		$progress[self::TRACKING_STATUS_EWI] = ['key' => self::TRACKING_STATUS_EWI, 'value' => 'Expired Without Installation'];
		$progress[self::TRACKING_STATUS_TC] = ['key' => self::TRACKING_STATUS_TC, 'value' => 'Tracking Completed'];

		if($key && $key !== ''){

			if(array_key_exists($key, $progress)){

				return $progress[$key];
			}

			return false;
		}

		return array_values($progress);
	}

	public function shipment_timeline($shipment){

		$timeline = [];
		$timeline_updates = [];

		if($shipment->status > 0){
			$shipment_tracking_pulse_model  = new ShipmentTrackingPulseModel();
			$trackings = ShipmentTrackingPulseModel::where('shipment_id', $shipment->row_id)->whereIn('tracking_type', ['arrived', 'started']);
			if($trackings->count() > 0){

				$trackings = $trackings->get();

				foreach($trackings as $tracking){
					$timeline_updates[$tracking->tracking_type] = $tracking;
				}
			}
		}

		$timeline['pending'] = ['key' => 'pending', 'label' => 'Shipment Created', 'heading' => '', 'sub_heading' => '', 'status' => ''];
		$timeline['arrived'] = ['key' => 'arrived', 'label' => 'Arrived At Location', 'heading' => '', 'sub_heading' => '', 'status' => ''];
		$timeline['started'] = ['key' => 'started', 'label' => 'Shipment Started', 'heading' => '', 'sub_heading' => '', 'status' => ''];
		$timeline['delivered'] = ['key' => 'delivered', 'label' => 'Shipment Delivered', 'heading' => '', 'sub_heading' => '', 'status' => ''];
		
		$timeline['pending']['heading'] = $shipment->added_on_formatted;
		$timeline['pending']['sub_heading'] = $shipment->shippment_carrier_label;
		$timeline['pending']['status'] = 'done';

		if(array_key_exists('arrived', $timeline_updates)){

			$timeline['arrived']['heading'] = date("d M Y, H:i A", strtotime($timeline_updates['arrived']->added_on));
			$timeline['arrived']['sub_heading'] = $shipment->shippment_carrier_label;
			$timeline['arrived']['status'] = 'current';
		}

		if(array_key_exists('started', $timeline_updates)){

			$timeline['arrived']['status'] = 'done';

			$timeline['started']['heading'] = date("d M Y, H:i A", strtotime($timeline_updates['started']->added_on));
			$timeline['started']['sub_heading'] = $shipment->shippment_carrier_label;
			$timeline['started']['status'] = 'current';
		}

		if($shipment->status == self::STATUS_DELIVERED){

			$timeline['started']['status'] = 'done';

			$timeline['delivered']['heading'] = $shipment->added_on_formatted;
			$timeline['delivered']['sub_heading'] = $shipment->shippment_carrier_label;
			$timeline['delivered']['status'] = 'current';
		}

		return $timeline;
	}

	public function create_shipment_number(){

        $shipments_increment_model     = new ShipmentsIncrementModel();
		$row = $shipments_increment_model->last_increment($shipments_increment_model::TYPE_SHIPMENT);

		if($row !== false){

			$next_increment = $row->increment + 1;

			$order_number = str_pad($next_increment, "6", "0", STR_PAD_LEFT);

			return $row->prefix . $order_number;
		}

		return false;
	}


	public function address_convert($request, $user){
		$shipment_tracking_google_map = new ShipmentTrackingGoogleMap();
		
		$address = $request->address;
		if ($address) {
			$coords = $shipment_tracking_google_map->address_to_coords($address);
			if($coords !== false){
				return ['status' => true, 'coords' => $coords];
			}else{
				return ['status' => false, 'message' => 'No coordinates available.'];
			}
						
		} else {
			return [ 'status'  => false, 'message' => 'Address is required.',  ];
		}
	}


	public function single_view($request, $user){
		//dd($request);
		$shipment_tracking_google_map = new ShipmentTrackingGoogleMap();
		
		$row_id = $request->row_id;
		if ($row_id) {
			$shipments_stops_model          = new ShipmentsStopsModel();
			$shipments_updates_model        = new ShipmentsUpdatesModel();

			$shipments_tracking_methods     = new ShipmentsTrackingMethodsModel();
			$shipments_carriers             = new ShipmentsCarriersModel();

			$shipment_tracking_pulse_model  = new ShipmentTrackingPulseModel();

			$shipments_action_centre_model  = new ShipmentsActionCentreModel();

			$drivers_model                  = new DriversModel();

			$shipments_documents            = new ShipmentsDocuments();


			$shipments_tracking_table = $shipments_tracking_methods->getTable();
			$shipments_carriers_table = $shipments_carriers->getTable();

			
			$shipment_row = self::with([
				'carrier:row_id,title',
				'trackingMethod:row_id,title'
			])->where('row_id', $row_id);

			if($shipment_row->count() > 0){
				$shipment = $shipment_row->first();

				if($shipment){
					$shipment->tracking_title = $shipment->trackingMethod->title ?? null;
					$shipment->carrier_title = $shipment->carrier->title ?? null;

					$shipment = $this->format($shipment);

					$shipment->stops = $shipments_stops_model->shipment_stops($row_id);

					$shipment_progress = $shipments_updates_model->progress('', true);

					$shipment_tracks = $shipment_tracking_pulse_model->shipment_tracks($row_id);
					

					$coords = [];

					foreach($shipment_tracks as $shipment_track){

						if($shipment_track->lat != '' && $shipment_track->long != ''){
			
							$coords[] = [
								'lat' => number_format((float)$shipment_track->lat, 8, '.', ''),
								'lng' => number_format((float)$shipment_track->long, 8, '.', ''),
								'date' => date("d M Y h:i A", strtotime($shipment_track->added_on))
							];
						}
					}

					/*
					Load driver
					*/
					$driver = false;
					if(isset($shipment->driver) && $shipment->driver!=''){
						$driver = $drivers_model->fetch_row_by_id($shipment->driver);
					}
					$_driver = false;
					if(isset($driver->row_id)){
						$_driver = $drivers_model->format($driver);
					}

					/*
					Action center
					*/
					$action_center = $shipments_action_centre_model->fetch_row_by_field('shipment_id', $shipment->row_id);
					
					$shipment->action_center = [];

					if($action_center){

						$shipment_progress[$shipments_updates_model::PROGRESS_DRIVER_ASSIGNED]['status'] = 'current';
						$shipment_progress[$shipments_updates_model::PROGRESS_DRIVER_ASSIGNED]['date'] = date("d M Y, h:i A", strtotime($action_center->added_on));

						if($_driver !== false){
							$shipment_progress[$shipments_updates_model::PROGRESS_DRIVER_ASSIGNED]['label'] = "<div><strong>Driver:</strong><span className='fw-semibold' style='margin-left:5px;'>" . ucwords(clean_display($_driver->name)) . "</span></div>";
						}

						if($action_center->request_status == '1'){

							$shipment_progress[$shipments_updates_model::PROGRESS_DRIVER_ASSIGNED]['status'] = 'visited';

							$shipment_progress[$shipments_updates_model::PROGRESS_READY_TO_TRACK]['status'] = 'current';
							$shipment_progress[$shipments_updates_model::PROGRESS_READY_TO_TRACK]['date'] = date("d M Y, h:i A", strtotime($action_center->request_updated_on));
							$shipment_progress[$shipments_updates_model::PROGRESS_READY_TO_TRACK]['label'] = "<strong>Driver accepted shipment.</strong>";
						}

						if(is_array($shipment_tracks) && array_key_exists('arrived', $shipment_tracks)){

							$shipment_progress[$shipments_updates_model::PROGRESS_READY_TO_TRACK]['status'] = 'visited';

							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_ORIGIN]['status'] = 'current';
							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_ORIGIN]['date'] = date("d M Y, h:i A", strtotime($shipment_tracks['arrived']->added_on));
							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_ORIGIN]['label'] = "<strong>Driver arrived at location.</strong>";
						}

						if(is_array($shipment_tracks) && array_key_exists('started', $shipment_tracks)){

							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_ORIGIN]['status'] = 'visited';

							$shipment_progress[$shipments_updates_model::PROGRESS_DEPARTED_ORIGIN]['status'] = 'current';
							$shipment_progress[$shipments_updates_model::PROGRESS_DEPARTED_ORIGIN]['date'] = date("d M Y, h:i A", strtotime($shipment_tracks['started']->added_on));
							$shipment_progress[$shipments_updates_model::PROGRESS_DEPARTED_ORIGIN]['label'] = "<strong>Shipment departed from location.</strong>";
						}

						if(is_array($shipment_tracks) && array_key_exists('transit', $shipment_tracks)){

							$shipment_progress[$shipments_updates_model::PROGRESS_DEPARTED_ORIGIN]['status'] = 'visited';

							$shipment_progress[$shipments_updates_model::PROGRESS_IN_TRANSIT]['status'] = 'current';
							$shipment_progress[$shipments_updates_model::PROGRESS_IN_TRANSIT]['date'] = time_elapsed_string($shipment_tracks['transit']->added_on);
							$shipment_progress[$shipments_updates_model::PROGRESS_IN_TRANSIT]['label'] = "<strong>Shipment In Transit.</strong>";
						}

						if(is_array($shipment_tracks) && array_key_exists('delivered', $shipment_tracks)){

							$shipment_progress[$shipments_updates_model::PROGRESS_IN_TRANSIT]['status'] = 'visited';

							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_DESTINATION]['status'] = 'current';
							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_DESTINATION]['date'] = date("d M Y, h:i A", strtotime($shipment_tracks['delivered']->added_on));
							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_DESTINATION]['label'] = "<strong>Shipment delivered.</strong>";
						}

						if(is_array($shipment_tracks) && array_key_exists('destination_left', $shipment_tracks)){

							$shipment_progress[$shipments_updates_model::PROGRESS_ARRIVED_AT_DESTINATION]['status'] = 'visited';

							$shipment_progress[$shipments_updates_model::PROGRESS_DEPARTED_DESTINATION]['status'] = 'current';
							$shipment_progress[$shipments_updates_model::PROGRESS_DEPARTED_DESTINATION]['date'] = time_elapsed_string($shipment_tracks['destination_left']->added_on);
							$shipment_progress[$shipments_updates_model::PROGRESS_DEPARTED_DESTINATION]['label'] = "<strong>Departed from destination.</strong>";
						}

						
						$action_center->app_installed = 'no';

						if($action_center->app_status == '1'){
							$action_center->app_installed = 'yes';
						}

						$action_center->device = '';
						
						if(isset($_driver->device) && $_driver->device != ''){
							
							$action_center->device = $_driver->device == 'android' ? 'Android' : 'iOS';
						}
						
						$shipment->action_center = $action_center;
					}

					
					$shipment->progress = array_values($shipment_progress);

					if($shipment->status == '4'){
						/* Load docs */
						$documents = $shipments_documents->load_documents($shipment->row_id);
						$shipment->documents = $documents;
					}
					
					return ['status' => true, 'shipment' => $shipment, 'coords' => $coords];
				}else{
					return ['status' => true, 'shipment' => false];
				}
			}else{

				return ['status' => true, 'shipment' => false];
			}
						
		} else {
			return [ 'status'  => false, 'message' => 'Address is required.',  ];
		}
	}

	///
	public function single_single($request, $user){
		$shipment_tracking_google_map = new ShipmentTrackingGoogleMap();
		
		$row_id = $request->row_id;
		if ($row_id) {
			$shipments_stops_model          = new ShipmentsStopsModel();
			$shipments_updates_model        = new ShipmentsUpdatesModel();

			$shipments_tracking_methods     = new ShipmentsTrackingMethodsModel();
			$shipments_carriers             = new ShipmentsCarriersModel();

			///
			$shipments_tracking_table = $shipments_tracking_methods->getTable();
			$shipments_carriers_table = $shipments_carriers->getTable();


			$shipment_row = self::with([
				'carrier:row_id,title',
				'trackingMethod:row_id,title'
			])->where('row_id', $row_id);


			if($shipment_row->count() > 0){
				$shipment = $shipment_row->first();

				if($shipment){
					$shipment->tracking_title = $shipment->trackingMethod->title ?? null;
					$shipment->carrier_title = $shipment->carrier->title ?? null;

					$shipment = $this->format($shipment);
					$shipment->stops = $shipments_stops_model->shipment_stops($row_id);
					$shipment->progress = $shipments_updates_model->progress();
					
					return ['status' => true, 'shipment' => $shipment];
				}else{
					return ['status' => true, 'shipment' => false];
				}
			}else{
				return ['status' => true, 'shipment' => false];
			}
						
		} else {
			return [ 'status'  => false, 'message' => 'Error Row Id', 'shipment' => false];
		}
	}

	public function tracking_pulses($request, $user){
		$shipments_documents = new ShipmentsDocuments();
		$shipment_tracking_pulse_model =  new ShipmentTrackingPulseModel();
		
		$row_id = $request->row_id;
		if ($row_id) {
			$shipment = $this->fetch_row_by_id($row_id);
			if($shipment !== false){
				list($coords, $tracking_pulses) = $shipment_tracking_pulse_model->tracking_pulses($row_id, $last_pulse);

				if($shipment->status == '4'){ 
					/* Load docs */
					$documents = $shipments_documents->load_documents($shipment->row_id);

					$shipment->documents = $documents;
				}

				return ['status' => true, /*'coords' => $coords, 'pulses' => $tracking_pulses,*/ 'shipment' => $shipment];
			}else{
				return ['status' => false, 'message' => 'No data available.', 'pulses' => []];
			}
						
		} else {
			return [ 'status'  => false, 'message' => 'Row Id is required.',  ];
		}
	}
    

	public function update_sort_order($request, $user){
		$shipments_documents = new ShipmentsDocuments();
		$shipment_tracking_pulse_model = new ShipmentTrackingPulseModel();
		$shipments_stops_model          = new ShipmentsStopsModel();
		
		$sort_order = $request->sort_order;
		if ($sort_order) {
			$sort_order = @json_decode($sort_order, true);
			if(is_array($sort_order) && count($sort_order) > 0){

				foreach($sort_order as $row_id => $_order) {
					$updated = ShipmentsStopsModel::where('row_id', $row_id)->update(['sort_order' => $_order,]);
				}
			}

			return ['status' => true];		
		} else {
			return [ 'status'  => false, 'message' => 'Sort Order is required.',  ];
		}
	}
    
    
	public function shipment_load($request, $user){
		$date = $request->date;

		if($date != ''){
			//entry_date
			$shipments = self::whereMonth('added_on', date('m', strtotime($date)))->whereYear('added_on', date('Y', strtotime($date)));

			$_shipments = [];

			if($shipments->count() > 0){

				$shipments = $shipments->get();

				foreach($shipments as $shipment){

					$shipment = $this->format($shipment);

					if(array_key_exists($shipment->added_on, $_shipments)){

						$_all_shipments = $_shipments[$shipment->added_on]['shipments'];

						$_shipments[$shipment->added_on] = ['primary_label' => count($_shipments) + 1 . ' Shipments', 'date' => $shipment->added_on];

						$_shipments[$shipment->added_on]['shipments'] = $_all_shipments;
						$_shipments[$shipment->added_on]['shipments'][] = $shipment;
					}else{

						$_shipments[$shipment->added_on] = ['primary_label' => '1 Shipment', 'date' => $shipment->added_on];
						$_shipments[$shipment->added_on]['shipments'][] = $shipment;
					}
				}
			}

			return ['status' => true, 'shipments' => array_values($_shipments)];
		}else{

			return ['status' => true, 'shipments' => []];
		}
	}
}
