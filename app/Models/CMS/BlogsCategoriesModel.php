<?php

namespace App\Models\CMS;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\CMS\CMSBlogArticles;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class BlogsCategoriesModel extends CoreModel
{
    	  

    protected $table = 'categories';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    

	public function format($category = false){

		if($category){


			$category->name = clean_display(stripslashes(stripslashes($category->name)));

			$category->description = nl2br(clean_display($category->description));

			$category->description_clipped = '';

			if(strlen($category->description) > 300){
				$category->description_clipped = substr(strip_tags($category->description), 0, 300) . '...';
			}

			$category->banner_url = '';
			if($category->page_banner != ''){
				$category->banner_url = URL::to(Storage::url("uploads/categories/banners/".clean_display($category->page_banner)));
			}

			$category->thumb_url = '';
			if($category->thumb_image != ''){
				$category->thumb_url = URL::to(Storage::url("uploads/categories/thumbs/".clean_display($category->thumb_image)));
			}

			$category->large_url = '';
			if($category->large_image != ''){
				$category->large_url = URL::to(Storage::url("uploads/categories/large/".clean_display($category->thumb_image)));
			}

			//$category->url = 'books/' . clean_display($category->slug);
		}

		return $category;
	}

	public function before_category_save($post = [], $action = '', $fields = [],  $user = false, $account_token = ''){
		//dd($post);
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


	public function after_category_save($post =[], $post_data = [], $row_id = '', $action = ''){

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

	 public function load_categories($params = []){
		if ($params instanceof \Illuminate\Http\Request) {
			$params = $params->all();
		}

        $parent = array_key_exists('parent', $params) ? $params['parent'] : '';
        $level = array_key_exists('level', $params) ? $params['level'] : '0';
        $include_childs = array_key_exists('include_childs', $params) ? $params['include_childs'] : true;

        	$categories_query = self::query();

		if(array_key_exists('group', $params)){
            $categories_query->where('categories_group', trim($params['group']));
		}

		if(array_key_exists('categories_group', $params)){
            $categories_query->where('categories_group', trim($params['categories_group']));
		}

		if(array_key_exists('active_only', $params)){

            $categories_query->where('status', self::STATUS_ACTIVE);
		}

		$categories_query->orderBy('order_by', 'asc');

		$categories = $categories_query->get();

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


	public function get_by_slug($slug = false){

		if($slug != ''){

			$category = $this->fetch_row_by_field('slug', $slug);

			return $category;
		}

		return false;
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

	///
	public function category_remove_after($db_field, $_row_id_value, $input_field, $user, $entries){

		$cms_blog_articles = new CMSBlogArticles();

		if(is_array($entries)){

			foreach($entries as $row){

				//unlink("./media/uploads/blogs/categories/" . clean_display($row->page_banner));
				//unlink("./media/uploads/blogs/categories/" . clean_display($row->large_image));
				//unlink("./media/uploads/blogs/categories/" . clean_display($row->thumb_image));

				/*
				Remove author from articles also
				*/

				$category_articles = CMSBlogArticles::whereRaw('FIND_IN_SET(?, categories)', [$row->row_id]);
				if($category_articles->count() > 0){

					$category_articles = $category_articles->get();

					foreach($category_articles as $category_article){
						
						if($category_article->categories != ''){
							
							$_categories = @explode(',', clean_display($category_article->categories));

							$article_categories = [];

							if(is_array($_categories)){

								foreach($_categories as $_category){

									if($_category != $row->row_id){

										$article_categories[] = trim($_category);
									}
								}
							}

							CMSBlogArticles::where('row_id', $category_article->row_id)->update(['categories' => implode(',', $article_categories),]);
						}
					}
				}
			}
        }
    }

	
}
