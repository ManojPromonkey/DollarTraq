<?php

namespace App\Models\Customers;

use App\Core\CoreModel;

use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class CustomersQuestionsModel extends CoreModel{
	const ANSWER_TYPE_YES_NO   = 'yes_no';
	const ANSWER_TYPE_TEXT     = 'text';
	const ANSWER_TYPE_TEXTAREA = 'textarea';
	const ANSWER_TYPE_NUMBER   = 'number';
	const ANSWER_TYPE_DROPDOWN = 'dropdown';
	const ANSWER_TYPE_CHECKBOX = 'checkbox';
	const ANSWER_TYPE_IMAGE    = 'image';

    	protected $table = 'customers_questions';
	public $timestamps = false;


    	function __construct(){
        $this->setTableIndex('row_id');
	}

    	public function format($row = false){
		if($row){
			$row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');
		}
		return $row;
	}


	public function answer_types(){
		return [
			['key' => self::ANSWER_TYPE_YES_NO,   'value' => 'Yes / No'],
			['key' => self::ANSWER_TYPE_TEXT,     'value' => 'Text'],
			['key' => self::ANSWER_TYPE_TEXTAREA, 'value' => 'Textarea'],
			['key' => self::ANSWER_TYPE_NUMBER,   'value' => 'Number'],
			//['key' => self::ANSWER_TYPE_DROPDOWN, 'value' => 'Dropdown'],
			//['key' => self::ANSWER_TYPE_CHECKBOX, 'value' => 'Checkbox'],
			['key' => self::ANSWER_TYPE_IMAGE,    'value' => 'Image Upload'],
		];
	}

	public function question_save_before($post = [], $action = '', $fields = [], $user = false, $account_token = ''){

		$return = [];

		$return['customer_id'] = $user['row_id'];

		return $return;
	}

	public function get_questions($request, $user){
        $query = self::where('customer_id', $user['row_id'])->orderBy('id', 'desc');
        //$query = $query->get();
        return $query;
	}

	
}