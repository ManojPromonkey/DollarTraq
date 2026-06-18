<?php

namespace App\Models\Core\Categories;

use App\Core\CoreModel;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;


class CategoriesModel extends CoreModel
{
    
    protected $table = 'categories';

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    function __construct(){
		
        $this->setTableIndex('row_id');
	}

	public function category_save_before($post = [], $action = '', $fields = [],  $user = false, $account_token = ''){

		if($action == 'save'){
			////
			$slug = Str::slug($post['name']);

			$originalSlug = $slug;
			$count = 1;

			while(self::where('slug', $slug)->exists()) {
				$slug = $originalSlug . '-' . $count++;
			}
			return ['slug' => $slug];
		}else{

			/*
			Load category to check if the parent has been changed and updated the childs
			*/
			$category = $this->fetch_row_by_id($post['row_id']);
			if($category){
				if(array_key_exists('parent', $post) && $category->parent != '' && $category->parent != $post['parent']){

					/*Update old parent childs*/
					$childs = self::where('parent', trim($category->parent))->count();

					$this->set_post_data('items_count', ($childs - 1) >= 0 ? $childs - 1 : 0);
					$this->post_update(trim($category->parent));
				}
			}
		}
	
		return [];
	}

	//$post_data, $row_id, $action, $user, $request
	///public function category_save_after(/*$post = [],*/ $post_data = [], $row_id = '', $action = '', $user = false, $request){
	public function category_save_after($post =[], $post_data =[], $row_id = '', $action = ''){

		if($row_id != ''){

			$childs = self::where('parent', trim($row_id))->count();

			$this->set_post_data('items_count', $childs);
			$this->post_update($row_id);

			/*
			Update all child category levels recursively
			*/
			$this->update_child_levels($row_id, $post_data['level'], true);
		}

		if(array_key_exists('parent', $post_data) && $post_data['parent'] != ''){

			$childs = self::where('parent', trim($post_data['parent']))->count();

			$this->set_post_data('items_count', $childs);
			$this->post_update(trim($post_data['parent']));
		}

	}

	public function load_categories_hook($request, $user){

		$category_group = $request->post('group');

		$_categories = [];

		if($category_group){

	        $_categories = $this->load_categories(['include_childs' => false, 'group' => $category_group]);
		}

		return [
            'status' => true,
            'categories' => $_categories,
            'group' => $category_group
        ];
	}

    public function load_categories($params = []){
		if ($params instanceof \Illuminate\Http\Request) {
			$params = $params->all();
		}

		$parent = array_key_exists('parent', $params) ? $params['parent'] : '';
		$level = array_key_exists('level', $params) ? $params['level'] : '0';
		$include_childs = array_key_exists('include_childs', $params) ? $params['include_childs'] : true;

		$categories_query = self::query();

		if (array_key_exists('group', $params)) {
			$categories_query->where('categories_group', trim($params['group']));
		}

		if (array_key_exists('categories_group', $params)) {
			$categories_query->where('categories_group', trim($params['categories_group']));
		}

		if (array_key_exists('active_only', $params)) {
			$categories_query->where('status', self::STATUS_ACTIVE);
		}

		$categories = $categories_query->orderBy('order_by', 'asc')->get();

		$_categories = [];

		if($categories->count() > 0){

			foreach($categories as $category){

				$childs = [];

				if($parent != ''){

					if($parent == $category->row_id){

						if($level == $category->level){

							if($include_childs){
							
								if($category->items_count > 0){
									$childs = $this->category_childs($categories, $category->row_id);
								}

								$category->childs = $childs;
							}

							$_categories[] = $this->format($category);
						}
					}
				}else{

					if($level == $category->level){

						if($include_childs){
							if($category->items_count > 0){
								$childs = $this->category_childs($categories, $category->row_id);
							}

							$category->childs = $childs;
						}

						$_categories[] = $this->format($category);
					}
				}
			}
		}

		return $_categories;
	}

	public function category_childs($categories = [], $parent_id = ''){

		$childs = [];

		foreach($categories as $category){

			if($category->parent == $parent_id){

				if($category->items_count > 0){
					$category->childs = $this->category_childs($categories, $category->row_id);
				}

				$childs[] = $this->format($category);
			}
		}

		return $childs;
	}

	public function category_filters($request, $user){

		$category_id = $request->post('category');
		$level = $request->post('level');

		$filters = [];

		if($category_id){

			$_categories = [];

			if($level == ''){

				$category = $this->fetch_row_by_id($category_id);

				if($category){
				
					$level = $category->parent;
				}
			}

			if($level == '0'){

				/*Load top level categories only*/

				$child_categories = self::where('parent', $category_id)->get();
				if($child_categories->count()){
					foreach($child_categories as $child_category){
						$_categories[] = ['id' => $child_category->id, 'label' => ucwords(clean_display($child_category->name)), 'url' => '/books/' . clean_display($child_category->slug)];
					}
				}
			}

			$filters[] = ['filter' => 'categories',  'label' => 'Categories', 'type' => 'link', 'list' => $_categories];
		}

		return ['status' => true, 'filters' => $filters];
	}

	public function format($category = false){

		if($category){
			$category->created_at_formatted 	= Carbon::parse($category->created_at)->format('d M, Y');
			$category->updated_at_formatted 	= Carbon::parse($category->updated_at)->format('d M, Y');

			$category->name = clean_display($category->name);

			$category->description = nl2br(clean_display($category->description));

			$category->description_clipped = '';

			if(strlen($category->description) > 300){
				$category->description_clipped = Str::limit(strip_tags($category->description), 300, '...');
			}

			$category->page_banner_url = '';
			if($category->page_banner != ''){
				$category->page_banner_url = URL::to(Storage::url("uploads/categories/banners/".clean_display($category->page_banner)));
			}

			$category->thumb_image_url = '';
			if($category->thumb_image != ''){
				$category->thumb_image_url = URL::to(Storage::url("uploads/categories/thumbs/".clean_display($category->thumb_image)));
			}

			$category->large_image_url = '';
			if($category->large_image != ''){
				$category->large_image_url = URL::to(Storage::url("uploads/categories/large/".clean_display($category->thumb_image)));
			}

			//$category->url = 'books/' . clean_display($category->slug);
		}

		return $category;
	}

	public function update_child_levels($category_id = false, $level = '', $continue = false){

		if($category_id != false && $level != '' && $continue == true){

			$childs = self::where('parent', $category_id)->get();

			if($childs->count() > 0){

				foreach($childs as $child){

					$this->set_post_data('level', $level + 1);
					$this->set_post_data('updated_at', date('Y-m-d H:i:s'));
					$this->post_update($child->row_id);

					$this->update_child_levels($child->row_id, $level + 1, true);
				}
			}
		}

		return false;
	}


	public function category_list($params = []){
		if ($params instanceof \Illuminate\Http\Request) {
			$params = $params->all();
		}

		/*if ($params instanceof Request) {
			$params = $params->all();
		}*/

		//dd($params);

		
		$categories_query = self::query();

		if (array_key_exists('status', $params)) {
			$categories_query->where('status', trim($params['status']));
		}

		if (array_key_exists('categories_group', $params)) {
			$categories_query->where('categories_group', trim($params['categories_group']));
		}

		if (array_key_exists('parent', $params)) {
			$categories_query->where('parent', trim($params['parent']));
		}

		if (array_key_exists('level', $params)) {
			$categories_query->where('level', trim($params['level']));
		}

		if (array_key_exists('include_childs', $params)) {
			$categories_query->where('include_childs', trim($params['include_childs']));
		}

		//$categories = $categories_query->get();
		return $categories_query;
	}


	public function frontend_category_list($request, $user){
       	//dd($request);
		$categories_query = self::where('status', self::STATUS_ACTIVE)->orderBy('order_by', 'asc');

		if (!empty($request->categories_group)) {
			$categories_query->where('categories_group', $request->categories_group);
		}

		if (!empty($request->parent)) {
			$categories_query->where('parent', $request->parent);
		}

		if (!empty($request->level)) {
			$categories_query->where('level', $request->level);
		}

		if (!empty($request->include_childs)) {
			$categories_query->where('include_childs', trim($request->include_childs));
		}

		$categories = array();
		foreach($categories_query->get() as $category){
	   		$category->page_banner_url = '';
			if($category->page_banner != ''){
				$category->page_banner_url = URL::to(Storage::url("uploads/categories/banners/".clean_display($category->page_banner)));
			}

			$category->thumb_image_url = '';
			if($category->thumb_image != ''){
				$category->thumb_image_url = URL::to(Storage::url("uploads/categories/thumbs/".clean_display($category->thumb_image)));
			}

			$category->large_image_url = '';
			if($category->large_image != ''){
				$category->large_image_url = URL::to(Storage::url("uploads/categories/large/".clean_display($category->thumb_image)));
			}	
			
			$categories[]		= $category;
		}

		return $categories;
    }


    public function categories_init($request = null, $user = null){
		$categories = [];
		//dd($request);
		$category_list = $this->frontend_category_list($request, $user);
		foreach($category_list as $list){	
	     	$categories[] = ['key' => $list->row_id, 'value' => $list->name];
		}
        	return $categories;
    }

    public function category_slug_view($request, $user){
       	//dd($request);
		$categories_query = array();
		$categories_query = self::where('status', self::STATUS_ACTIVE);

		if (!empty($request->categories_group)) {
			$categories_query->where('categories_group', $request->categories_group);
		}

		if (!empty($request->parent)) {
			$categories_query->where('parent', $request->parent);
		}

		if (!empty($request->level)) {
			$categories_query->where('level', $request->level);
		}

		if (!empty($request->slug)) {
			$categories_query->where('slug', trim($request->slug));
		}

        	return $categories_query->first();
    }
}
