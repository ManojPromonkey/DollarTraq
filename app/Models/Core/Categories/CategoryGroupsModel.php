<?php

namespace App\Models\Core\Categories;
use Illuminate\Support\Facades\DB;

use App\Core\CoreModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CategoryGroupsModel extends CoreModel
{
    protected $table = 'categories_groups';

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    function __construct(){
		
        $this->setTableIndex('row_id');
	}

    public function category_groups(){
        return self::orderBy('title', 'asc')
            ->get()
            ->map(function ($category) {
                return $this->format($category);
            })
            ->toArray();
    }

    public function format($row = false){
		if($row){
            $row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');
			$row->title = clean_display($row->title);
		}
		return $row;
	}

public function category_group_before($post, $action, $fields, $user, $account_token, $row_id){
        $return = [];
        if($action == 'save'){
            if(array_key_exists('title', $post)){      

                $code = Str::slug($post['title']);

                $originalCode = $code;
                $count = 1;

                while(self::where('code', $code)->exists()) {
                    $code = $originalCode . '-' . $count++;
                }

                $return['code'] = $code;
            }
        }else{
            if(array_key_exists('code', $post)){
                $return['code'] = $this->validate_slug($post['code'], 'code', $this->getTable(), '_', $row_id, $this->getTableIndex());
            }            
        }

        return $return;
    }
}
