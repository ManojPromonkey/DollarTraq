<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use App\Models\Shipments\ShipmentsModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShipmentsUpdatesModel extends CoreModel
{
    const STATUS_ENABLED = 1;
	const STATUS_DISABLED = 0;

	const PROGRESS_DRIVER_ASSIGNED = 'assigned';
	const PROGRESS_READY_TO_TRACK = 'ready';
	const PROGRESS_ARRIVED_AT_ORIGIN = 'arrived';
	const PROGRESS_DEPARTED_ORIGIN = 'departed';
	const PROGRESS_IN_TRANSIT = 'in_transit';
	const PROGRESS_ARRIVED_AT_DESTINATION = 'destination';
	const PROGRESS_DEPARTED_DESTINATION = 'destination_left';

    protected $table = 'shipment_updates';

    function __construct(){
        $this->setTableIndex('row_id');
	}

	public function shipment(){
        return $this->belongsTo(ShipmentsModel::class, 'shipment_id', 'row_id');
	}

    public function format($shipment = false){

		if($shipment){
			$shipment->added_on_formatted 	= Carbon::parse($shipment->added_on)->format('d M Y');
			$shipment->send_updates_to 		= clean_display($shipment->send_updates_to);
		}

		return $shipment;
	}

	public function progress($key = '', $with_keys = false){
		$progress = [];

		$progress[self::PROGRESS_DRIVER_ASSIGNED] = ['key' => self::PROGRESS_DRIVER_ASSIGNED, 'value' => 'Driver Assigned'];
		$progress[self::PROGRESS_READY_TO_TRACK] = ['key' => self::PROGRESS_READY_TO_TRACK, 'value' => 'Ready To Track'];
		$progress[self::PROGRESS_ARRIVED_AT_ORIGIN] = ['key' => self::PROGRESS_ARRIVED_AT_ORIGIN, 'value' => 'Arrived At Origin'];
		$progress[self::PROGRESS_DEPARTED_ORIGIN] = ['key' => self::PROGRESS_DEPARTED_ORIGIN, 'value' => 'Departed Origin'];
		$progress[self::PROGRESS_IN_TRANSIT] = ['key' => self::PROGRESS_IN_TRANSIT, 'value' => 'In Transit'];
		$progress[self::PROGRESS_ARRIVED_AT_DESTINATION] = ['key' => self::PROGRESS_ARRIVED_AT_DESTINATION, 'value' => 'Arrived At Destination'];
		$progress[self::PROGRESS_DEPARTED_DESTINATION] = ['key' => self::PROGRESS_DEPARTED_DESTINATION, 'value' => 'Departed Destination'];

		if($key && $key !== ''){

			if(array_key_exists($key, $progress)){

				return $progress[$key];
			}

			return false;
		}

		return $with_keys == false ? array_values($progress) : $progress;
	}

	public function track_days(){

		$list = [];

		$list[] = ['key' => '1', 'value' => 'Track For 1 Day'];
		$list[] = ['key' => '2', 'value' => 'Track For 2 Days'];
		$list[] = ['key' => '3', 'value' => 'Track For 3 Days'];
		$list[] = ['key' => '4', 'value' => 'Track For 4 Days'];
		$list[] = ['key' => '5', 'value' => 'Track For 5 Days'];
		$list[] = ['key' => '6', 'value' => 'Track For 6 Days'];
		$list[] = ['key' => '7', 'value' => 'Track For 7 Days'];

		return $list;
	}

	public function track_time(){

		$list = [];

		$list[] = ['key' => '15', 'value' => "Every 15 mintues(s)"];
		$list[] = ['key' => '60', 'value' => "Every 1 hour"];
		$list[] = ['key' => '120', 'value' => "Every 2 hour"];
		$list[] = ['key' => '240', 'value' => "Every 4 hour"];

		return $list;
	}


    
}
