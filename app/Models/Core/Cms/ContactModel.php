<?php

namespace App\Models\Core\Cms;

use App\Core\CoreModel;
use Illuminate\Http\Request as Request;

class ContactModel extends CoreModel
{
    protected $table = 'contacts';

    function __construct(){
    
        $this->setTableIndex('row_id');
        $this->setTableName('contacts');
    }

    // public function before_page_save($post = [], $action = '', $fields = [],  $user = false, $account_token = ""){

    //     $return = [];

    //     if($action == 'save'){

    //         if(array_key_exists('title', $post)){
            
    //             $title = $post['title'];

    //             $slug = $this->generate_slug($title, '-');

    //             $slug = $this->validate_slug($slug, 'slug', $this->getTable(), '-');

    //             $return['slug'] = $slug;
    //         }
    //     }

    //     return $return;
    // }
}
