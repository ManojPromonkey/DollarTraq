<?php
namespace App\Models\Core\Cms;

use App\Core\CoreModel;

use App\Models\Core\Blogs\BlogArticlesModel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CmsPagesModel extends CoreModel
{
    protected $table = 'cms_pages';

    function __construct(){
        
        $this->setTableIndex('row_id');
    }

    public function format($row = false){

        if($row){

            $row->title = clean_display($row->title);
            // $row->content = clean_display($row->content);

			$row->meta_title = clean_display($row->meta_title);
			$row->meta_description = clean_display($row->meta_description);
			$row->meta_keywords = clean_display($row->meta_keywords);

			$row->added_on_formatted = Carbon::parse($row->added_on)->format('d M Y, h:i A');
        }

        return $row;
    }

	public function load_page($request, $user){

		$slug = $request->post('page');

		if($slug){

			$page = $this->fetch_row_by_field('slug', trim($slug));

			if($page){

				$page = $this->format($page);

				if($slug == 'home-page'){

					/*
					Blog Articles
					*/

					$blog_articles_model = new BlogArticlesModel;

					$page->blog_articles = $blog_articles_model->load_articles(['sort_by' => 'added_on', 'sort_dir' => 'desc'], 3, 1);
				}

				return ['status' => true, 'page' => $page];
			}
		}

		return ['status' => false, 'page' => []];
	}

    public function before_template_save($post, $action, $fields, $user, $account_token, $_row_id){

        $return = [];

        if($action == 'save'){
			$code = Str::slug($post['code']);

			$originalCode = $code;
			$count = 1;

			while(self::where('code', $code)->exists()) {
				$code = $originalCode . '-' . $count++;
			}

			$return['code'] = $code;
        }

        return $return;
    }
}