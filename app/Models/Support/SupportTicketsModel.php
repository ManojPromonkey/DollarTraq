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
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\URL;


use App\Models\Customers\CustomersModel;
use App\Models\CMS\CMSEmailModel;
use App\Models\Support\SupportTicketsMssagesModel;

use Illuminate\Support\Carbon;


class SupportTicketsModel extends CoreModel
{
	use Authenticatable, HasApiTokens, Notifiable;

	const PRIORITY_LOW = 'low';
	const PRIORITY_MEDIUM = 'medium';
	const PRIORITY_HIGH = 'high';
	const PRIORITY_URGENT = 'urgent';

	const STATUS_OPEN = 'open';
	const STATUS_PENDING = 'pending';
	const STATUS_PROGRESS = 'progress';
	const STATUS_RESOLVED = 'resolved';
	const STATUS_CLOSED = 'closed';


    	protected $table = 'support_tickets';
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

			$row->attachment_url = '';
			if(property_exists($row, 'attachment')){
				if($row->attachment !== ''){
					$row->attachment_url = URL::to(Storage::url("uploads/support_tickets/attachment/".clean_display($row->attachment)));
				}
			}

		}

		return $row;
	}

	
	public static function priority_list(){
		return [
			['key' => self::PRIORITY_LOW, 'value' => 'Low'],
			['key' => self::PRIORITY_MEDIUM, 'value' => 'Medium'],
			['key' => self::PRIORITY_HIGH, 'value' => 'High'],
			['key' => self::PRIORITY_URGENT, 'value' => 'Urgent',],
		];
	}

	public static function status_list(){
    		return [
        		['key' => self::STATUS_OPEN, 'value' => 'Open'],
			['key' => self::STATUS_PENDING, 'value' => 'Pending'],
        		['key' => self::STATUS_PROGRESS, 'value' => 'In Progress'],
        		['key' => self::STATUS_RESOLVED, 'value' => 'Resolved'],
        		['key' => self::STATUS_CLOSED, 'value' => 'Closed'],
    		];
	}
	

	public function before_save_ticket($post, $action, $fields, $user, $account_token, $_input_row_id=false){
		return ['customer_id' => $user['row_id']];
	}
    

	public function after_save_ticket($request, $post_data, $row_id, $action, $user){
        //dd($user);
        
		if($row_id != ''){

            $ticket_row = $this->fetch_row_by_id($row_id);

            if(isset($ticket_row->row_id)){
                $attachment = $request->filled('attachment') ? $request->post('attachment') : '';
                
                if($request->hasFile('attachment')){

                    $file = $request->file('attachment');
                    $fieldName = 'attachment';

                    $originalName = preg_replace('/[^a-zA-Z0-9.-]/', '', $file->getClientOriginalName());
                    $extension = strtolower($file->getClientOriginalExtension());
                    
                    $filename = md5(now() . '-' . $originalName . '-' . random_string_generator(20)) . '.' . $extension;
                    $directory_path = strtolower(random_string_generator(3)) . "/" . strtolower(random_string_generator(3));

                    $upload_directory = 'support_tickets/attachment/';

                    $storage_path = "uploads/" . $upload_directory . $directory_path . "/" . $filename;
                    $db_save_path = $directory_path . "/" . $filename;

                    if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){

                        $image = Image::read($file->getRealPath());

                        $fixSizeField = $fieldName . '_fix_size';

                        if($request->has($fixSizeField) && $request->{$fixSizeField} != ''){

                            $image = encodeToTargetSize($image, 'jpg', $request->{$fixSizeField} * 1024);
                            Storage::put($storage_path, $image);
                        }else{
                            Storage::put($storage_path, (string) $image->encodeByExtension($extension, 80));
                        }
                    }else{

                        Storage::put($storage_path, file_get_contents($file->getRealPath()));
                    }

                    $attachment = $db_save_path;
                }
			
			 $ticket_no	= 'DT'.($ticket_row->id+1000);
			 if($ticket_row->ticket_no!=''){
				$ticket_no	= $ticket_row->ticket_no;
			 }
			 $status	= 'open';
			 if($ticket_row->status!=''){
				$ticstatusket_no	= $ticket_row->status;
			 }

			self::where('row_id', $row_id)->update([
				'ticket_no'  => $ticket_no,
				'status'     => $status,
				'attachment' => $attachment,
				'updated_on' => now(),
			]);
			
			if($ticket_row->ticket_no==''){
				$this->ticket_mail_admin($ticket_row->row_id);
			 }
                return ['status' => true, 'message' => 'Your ticket has been submitted successfully.'];
                
            }            
            
		}

	}

	public function ticket_mail_admin($ticket_id){
		$ticket_row = $this->fetch_row_by_id($ticket_id);
		if(isset($ticket_row->row_id)){
			$html = '
				<h2>New Support Ticket Created</h2>

				<p><strong>Ticket ID:</strong> ' . $ticket_row->row_id . '</p>

				<p><strong>Ticket No. :</strong> ' . $ticket_row->ticket_no . '</p>

				<p><strong>Customer ID:</strong> ' . $ticket_row->customer_id . '</p>

				<p><strong>Priority:</strong> ' . ucfirst($ticket_row->priority) . '</p>

				<p><strong>Subject:</strong> ' . $ticket_row->subject . '</p>

				<p><strong>Message:</strong></p>

				<p>' . nl2br($ticket_row->message) . '</p>
			';

			$admin_email = env('ADMIN_EMAIL');
			Mail::send([], [], function ($message) use ($admin_email, $html, $ticket_row) {
				$message->to($admin_email)
				->subject('New Support Ticket - '.$ticket_row->ticket_no)
				->html($html);
			});

		}
	}


	public function customer_ticket_list($request, $user){
		//dd($user);
		$listing = self::where('customer_id', $user['row_id'])->orderByDesc('id');
        	return $listing;
	}

	public function ticket_view($request, $user){
		//dd($user);
		$support_tickets_mssagesModel	= new SupportTicketsMssagesModel();
		$ticket = self::where('row_id', $request->row_id)->first();
		$messages = $support_tickets_mssagesModel->messages_by_ticket($request->row_id);
		
        	return ['ticket' => $ticket, 'messages' => $messages];
	}
}
