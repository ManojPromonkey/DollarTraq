<?php

namespace App\Models\Core\Categories;

use App\Core\CoreModel;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use Illuminate\Http\Request;

use Illuminate\Support\Str;

use Illuminate\Support\Carbon;

class Categories extends CoreModel
{
    
    protected $table = 'categories';

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    function __construct(){
        $this->setTableIndex('row_id');
	}

	public function children(){
		return $this->hasMany(self::class, 'parent', 'row_id')->orderBy('order_by');
	}

	public function childrenRecursive(){
		return $this->children()->with('childrenRecursive');
	}

	public function category_save_before($post = [], $action = '', $fields = [],  $user = false, $account_token = ''){

		if($action == 'save'){
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
					/*
					Update old parent childs
					*/
					$childs = self::where('parent', trim($category->parent))->count();

					$this->set_post_data('items_count', ($childs - 1) >= 0 ? $childs - 1 : 0);
					$this->post_update(trim($category->parent));
				}
			}
		}
	
		return [];
	}

	public function category_save_after($post_data = [], $row_id = '', $action = ''){

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

		return true;
	}

    public function load_categories($params = []){
		if ($params instanceof Request) {
			$params = $params->all();
		}

		$parent         = $params['parent'] ?? '';
		$level          = $params['level'] ?? 0;
		$includeChilds  = $params['include_childs'] ?? true;

		$query = self::query();

		if (!empty($params['group'])) {
			$query->where('categories_group', trim($params['group']));
		}

		if (!empty($params['active_only'])) {
			$query->where('status', self::STATUS_ACTIVE);
		}

		$query->where('level', $level);

		if (!empty($parent)) {
			$query->where('row_id', $parent);
		}

		if ($includeChilds) {
			$query->with('childrenRecursive');
		}

		$categories = $query
			->orderBy('order_by')
			->get();

		return $categories->map(function ($category) {

			$category = $this->format($category);

			if (!empty($category->childrenRecursive)) {
				$category->childs = $category->childrenRecursive;
				unset($category->childrenRecursive);
			}

			return $category;

		})->values();
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

	public function format($category = false){

		if($category){
			$category->created_at_formatted 	= Carbon::parse($category->created_at)->format('d M, Y');
			$category->updated_at_formatted 	= Carbon::parse($category->updated_at)->format('d M, Y');

			//$category->name = clean_display(stripslashes(stripslashes($category->name)));

			$category->description = nl2br(clean_display($category->description));

			$category->description_clipped = '';

			if(strlen($category->description) > 300){
				$category->description_clipped = substr(strip_tags($category->description), 0, 300) . '...';
			}

			$category->page_banner_url = '';

			if($category->page_banner != ''){
				$category->page_banner_url = URL::to(Storage::url("uploads/categories/banners/".clean_display($category->page_banner)));
			}

			$category->thumb_image_url = '';
			if($category->thumb_image != ''){
				$category->thumb_image_url = URL::to(Storage::url("uploads/categories/banners/".clean_display($category->thumb_image)));
			}
		}

		return $category;
	}

	public function update_child_levels($category_id = null, $level = null, $continue = false){
		if (!$category_id || $level === null || !$continue) {
			return false;
		}

		$children = self::where('parent', $category_id)->get();

		foreach ($children as $child) {

			$child->update([
				'level'      => $level + 1,
				'updated_at' => now(),
			]);

			$this->update_child_levels(
				$child->row_id,
				$level + 1,
				true
			);
		}

		return true;
	}

}
