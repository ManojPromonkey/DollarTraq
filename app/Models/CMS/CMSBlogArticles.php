<?php

namespace App\Models\CMS;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\CMS\CMSBlogArticles;

use App\Models\CMS\BlogsCategoriesModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CMSBlogArticles extends CoreModel
{
    	const STATUS_DRAFT = 0;
	    const STATUS_PUBLISHED = 1;
	    const STATUS_DISABLED = 2;  

    	protected $table = 'blog_articles';

        function __construct(){
            $this->setTableIndex('row_id');
        }

        public function authorData(){
            return $this->belongsTo(CMSBlogAuthors::class, 'author', 'row_id');
        }

    
	public function format($row){

		if($row){

            if(isset($row->authorData->username)){
                $row->author_name = $row->authorData->name;
                $row->author_slug = $row->authorData->username;
                $row->author_thumb = $row->authorData->image;
            }

            $row->title = clean_display($row->title);
            $row->slug = clean_display($row->slug);
            $row->content = clean_display($row->content);
            $row->short_description = clean_display(clean_display(clean_display($row->short_description)));

            $row->posted_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');

            $row->excerpt = '';

            if($row->content != ''){
                $content = trim(strip_tags($row->content));
                $row->excerpt = substr($content, 0, 50);
            }

            $row->thumb_url = '';
            $row->image_url = '';

            if($row->thumb != ''){
                $row->thumb_url = URL::to(Storage::url('uploads/blogs/articles/' . clean_display($row->thumb)));
            }
            
            if($row->image != ''){
			$row->image_url = URL::to(Storage::url('uploads/blogs/articles/' . clean_display($row->image)));
            }

            $row->_categories = [];
            if($row->categories != ''){
                $row->_categories = explode(',', $row->categories);
            }

            if(property_exists($row, 'author_name')){
                $row->author_name = clean_display($row->author_name);
            }

            if(property_exists($row, 'author_slug')){
                $row->author_slug = clean_display($row->author_slug);
            }

            if(property_exists($row, 'author_profile')){
                $row->author_profile = clean_display($row->author_profile);
            }

            $row->author_image = '';
            if(property_exists($row, 'author_thumb')){
			    $row->author_image = URL::to(Storage::url('uploads/blogs/authors/' . clean_display($row->author_thumb)));
            }

            $row = $this->blogs_article_format($row);
        }

        return $row;
	}
	

	public function blogs_article_format($row){
		$blogs_categories_model = new BlogsCategoriesModel();

        	$category_ids = [];
        	$category_names = [];
        
        	$categories = explode(',', $row->categories);

		foreach($categories as $category_id){
			$category_ids[] = trim($category_id);
		}

        if(count($category_ids) > 0){
            $categories_list = BlogsCategoriesModel::whereIn('row_id', $category_ids);

            if($categories_list->count() > 0){
				$categories_list = $categories_list->get();

                foreach($categories_list as $category_item){
                    $categories_map[$category_item->row_id] = clean_display($category_item->name);
                    $category_names[] = clean_display($category_item->name);
                }
            }
        }

        $row->_category_names = implode(', ', $category_names);
                    
        return $row;
    }

    public function status_options(){

		return [
			['key' => self::STATUS_DRAFT, 'value' => 'Draft'],
			['key' => self::STATUS_PUBLISHED, 'value' => 'Published'],
			['key' => self::STATUS_DISABLED, 'value' => 'Disabled'],
		];
	}


	public function before_article_save($post, $action, $fields, $user, $account_token, $_input_row_id=false){

		$return = [];

        if(array_key_exists('categories', $post)){
        
            $categories = $post['categories'];

            $_categories = [];

            if($categories != ''){

                $categories = @json_decode($categories, true);

                if(is_array($categories)){

                    foreach($categories as $category){

                        if($category != ''){

                            $_categories[] = trim($category);
                        }
                    }
                }
            }

            $return['categories'] = implode(',', $_categories);

		  if($action == 'save'){
                ///
                $slug = Str::slug($post['title']);

                $originalSlug = $slug;
                $count = 1;

                while(self::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }

                $return['slug'] = $slug;
            }
        }

        return $return;
	}

	public function after_article_save($post =[], $post_data, $row_id, $action, $user=false){
		if($row_id){
            $article = $this->fetch_row_by_id($row_id);
            if($article){
                return ['article' => $this->format($article)];
            }
        }

        return [];
    }

    public function blog_articles(){
        $query = self::with(['authorData:row_id,name,username,image']);
		return $query;
	}

	public function article_remove_after($post =[], $post_data, $_row_id_value, $input_field, $user){
		if(is_array($_row_id_value)){
			foreach($_row_id_value as $row_id_value){
				$row = $this->fetch_row_by_id($row_id_value);
				if($row){
					//unlink("./media/uploads/blogs/articles/" . clean_display($row->image));
					//unlink("./media/uploads/blogs/articles/" . clean_display($row->thumb));
            		}
			}
        }
    }
	
}
