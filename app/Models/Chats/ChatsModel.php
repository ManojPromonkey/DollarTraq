<?php

namespace App\Models\Chats;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\Drivers\DriversModel;
use App\Models\Customers\CustomersModel;


class ChatsModel extends CoreModel
{
    
    	protected $table = 'chats';
	protected $fillable = ['sender_id', 'sender_type', 'receiver_id', 'receiver_type', 'shipment_id', 'message',];

    	function __construct(){
        $this->setTableIndex('row_id');
	}

   	public function format($row = false){
		return $row;
	}

	public function getTimeElapsedAttribute(){
		return time_elapsed_string($this->created_at);
	}

	public function getMessageAttribute($value){
		return clean_display($value);
	}

	public function customer(){
		return $this->belongsTo(CustomersModel::class,'sender_id', 'row_id');
	}

	public function chats_fetch($request, $user){
		$sender_id = $request->post('sender_id', '');
		$sender_type = $request->post('sender_type', '');
		$shipment_id = $request->post('shipment_id', '');

		$chats = self::where(function ($q) use ($sender_id, $sender_type, $shipment_id) {

			$q->where(function ($sub) use ($sender_id, $sender_type, $shipment_id) {
				$sub->where('sender_id', $sender_id)
					->where('sender_type', $sender_type)
					->where('shipment_id', $shipment_id);
			})->orWhere(function ($sub) use ($sender_id, $sender_type, $shipment_id) {
				$sub->where('shipment_id', $shipment_id)
					->where('receiver_id', $sender_id)
					->where('receiver_type', $sender_type);
			});

		})->orderBy('created_at')->get();

		return [
			'status' => true,
			'chats' => $chats,
		];
	}

	public function chats_list($request, $user){
		$messages = self::with([
				'customer:row_id,name'
			])->where('receiver_id', $user['row_id'])->where('receiver_type', 'driver')->orderByDesc('created_at')->get();

		$chats = [];

		$shipmentChats = $messages->groupBy('shipment_id');

		foreach ($shipmentChats as $shipmentId => $shipmentMessages) {

			$unreadMessages = $shipmentMessages
				->where('is_read', 0);

			$lastUnreadMessage = $unreadMessages
				->sortByDesc('created_at')
				->first();

			$firstMessage = $shipmentMessages->first();

			$nameShortcut = '';

			if (!empty($firstMessage?->customer?->name)) {

				$names = explode(
					' ',
					clean_display($firstMessage->customer->name)
				);

				$nameShortcut = count($names) > 1
					? strtoupper(
						substr($names[0], 0, 1) .
						substr($names[1], 0, 1)
					)
					: strtoupper(substr($names[0], 0, 2));
			}

			$chats[] = [
				'shipment_id'    => $shipmentId,
				'last_message'   => $lastUnreadMessage?->message ?? '',
				'messages_count' => $unreadMessages->count(),
				'name'           => $nameShortcut,
				'customer_id'    => $firstMessage?->customer?->row_id,
			];
		}

		return [
			'status' => true,
			'chats'  => array_values($chats),
		];
	}
	

	public function chats_send($request, $user){
		self::create([
			'sender_id'     => $request->post('sender_id', ''),
			'sender_type'   => $request->post('sender_type', ''),
			'receiver_id'   => $request->post('receiver_id', ''),
			'receiver_type' => $request->post('receiver_type', ''),
			'shipment_id'   => $request->post('shipment_id', ''),
			'message'       => $request->post('message', ''),
		]);

		return [
			'status'  => true,
			'message' => 'Message sent successfully.',
		];
	}
    
}

