<?php

namespace App\Models\Support;

use App\Core\CoreModel;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\URL;


use App\Models\Customers\CustomersModel;
use App\Models\CMS\CMSEmailModel;

use Illuminate\Support\Carbon;



class SupportTicketsMssagesModel extends CoreModel
{
	use Authenticatable, HasApiTokens, Notifiable;

    	protected $table = 'support_tickets_messages';
	public $timestamps = false;

    	function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($row = false){
		//dd($driver);
		if($row){
			if(property_exists($row, 'added_on')){
				$row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M Y');
			}
		}

		return $row;
	}

	public function before_save_messages($post, $action, $fields, $user, $account_token, $_input_row_id=false){
		//dd($user);
		//die(json_encode(['status' => false, 'user' => $user]));
		return ['sender_id' => $user['row_id']];
	}
    

	public function after_save_messages($request, $post_data, $row_id, $action, $user){
        //dd($user);
        
		if($row_id != ''){

            $message_row = $this->fetch_row_by_id($row_id);

            if(isset($message_row->row_id)){
			return ['status' => true, 'message' => 'Your message has been submitted successfully.'];
            }            
            
		}

	}


	public function messages_by_ticket($ticket_id){
		//dd($user);
		$listing = self::where('ticket_id', $ticket_id)->orderByDesc('id')->get();
        	return $listing;
	}

}
