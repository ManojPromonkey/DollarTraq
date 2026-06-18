<?php

namespace App\Models\CMS;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\CMS\CMSBlogArticles;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CMSBlogAuthors extends CoreModel
{
    	const STATUS_ENABLED = 1;
        const STATUS_DISABLED = 0;

        const VISIBILITY_AUTO = 'a'; // Listed automatically in the url's
        const VISIBILITY_MANUAL = 'm'; // Need to create manual controller

    	protected $table = 'authors';

        function __construct(){
            $this->setTableIndex('row_id');
        }

        public function format($row){

            if($row){
                $row->name = ucwords(clean_display($row->name));
                $row->image_url = '';
                if($row->image != ''){
                    $row->image_url = URL::to(Storage::url('uploads/blogs/authors/' . clean_display($row->image)));
                }
            }

            return $row;
        }
    
	
    public function authors(){
		$authors = [];

        $_authors = self::orderBy('name', 'asc');
		if($_authors->count() > 0){
			$_authors = $_authors->get();
			foreach($_authors as $_author){
				$authors[] = $this->format($_author);
			}
		}

		return $authors;
	}


    public function author_remove_after($post =[], $db_field, $_row_id_value, $input_field, $user){
        $cms_blog_articles = new CMSBlogArticles();

		if(is_array($_row_id_value)){

			foreach($_row_id_value as $row_id_value){
				$row = $this->fetch_row_by_id($row_id_value);

				if($row){

					//unlink("./media/uploads/blogs/authors/" . clean_display($row->image));

					/*
					Remove author from articles also
					*/
                    CMSBlogArticles::where('author', $row_id_value)->update(['author' => '',]);
            	}
			}
        }
    }

}
