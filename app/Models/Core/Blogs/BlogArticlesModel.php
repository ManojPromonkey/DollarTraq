<?php
namespace App\Models\Core\Blogs;

use App\Core\CoreModel;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

use App\Models\Core\Categories\CategoriesModel;
use App\Models\Core\Blogs\BlogAuthorsModel;

use Illuminate\Support\Carbon;


class BlogArticlesModel extends CoreModel
{
    protected $table = 'blog_articles';

    protected $appends = ['author_name', 'author_slug', 'author_thumb',];

    const STATUS_DRAFT = 0;
    const STATUS_PUBLISH = 1;
    const STATUS_DISABLED = 2;

    const BLOG_TYPE_FEATURE = 'Feature';
    const BLOG_TYPE_POPULAR = 'Popular';

    function __construct(){
        $this->setTableIndex('row_id');
    }

    public function authorData(){
        return $this->belongsTo(BlogAuthorsModel::class, 'author', 'row_id');
    }

    public function getAuthorNameAttribute(){
        return $this->authorData?->name;
    }

    public function getAuthorSlugAttribute(){
        return $this->authorData?->username;
    }

    public function getAuthorThumbAttribute(){
        return $this->authorData?->image;
    }

    public function getAuthorProfileAttribute(){
        return $this->authorData?->profile;
    }

    public function format($row = false){

        if($row){

            $row->posted_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');

            $row->excerpt = '';
            if($row->content != ''){
                $content = trim(strip_tags($row->content));
                $row->excerpt = Str::limit($content, 200);
            }

            $row->thumb_url = '';
            if($row->thumb != ''){
                $row->thumb_url = URL::to(Storage::url("uploads/blogs/articles/".clean_display($row->thumb)));
            }

            $row->image_url = '';
			if(isset($row->image) && $row->image != ''){
                $row->image_url = URL::to(Storage::url("uploads/blogs/articles/".clean_display($row->image)));
			}
            
            $row->_categories = [];
            if($row->categories != ''){
                $row->_categories = explode(',', $row->categories);
            }

            
            if(isset($row->author_image) && $row->author_image!=''){
                $row->author_image   = URL::to(Storage::url("uploads/author_images/".clean_display($author->image)));
            }else{
                $row->author_image      = '';
            }

            $row->author_name       = '';
            $row->author_slug       = '';
            if(isset($row->author) && $row->author!=''){
                $author_row             = BlogAuthorsModel::where('row_id', $row->author)->first();

                if(isset($author_row->row_id) && $author_row->row_id!=''){
                    $row->author_name   = $author_row->name;
                    $row->author_slug   = $author_row->username;
                    $row->author_image   = URL::to(Storage::url("uploads/author_images/".clean_display($author_row->image)));
                }
            }

            $row = $this->blogs_article_format($row);
        }

        return $row;
    }

    public function load_article_hook($request, $user){

        $row_id = $request->post('row_id');

        $return = [];
        $return['status'] = true;

        if($row_id){
            $article             = self::where('row_id', $row_id)->first();

            if($article){
                $article = $this->format($article);
                $return['article'] = $article;
            }else{
                $return['status'] = false;
            }
        }

        /*
        Load authors
        */
        $authors = [];

        $authors = BlogAuthorsModel::orderBy('name', 'asc')->get();
        if($authors_query->count()){

            foreach($authors_query as $author){

                $author->key = $author->row_id;
                $author->value = ucwords(clean_display($author->name));

                $authors[] = $blog_authors_model->format($author);
            }
        }

        $return['authors'] = $authors;

        /*Load status*/
        $return['statuses'] = $this->statuses();

        return $return;
    }

    public function article_save_before($post = [], $action = '', $fields = [],  $user = false, $account_token = '', $row_id, $request){
        
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
        }

        return $return;
    }

    public function article_save_after($post =[], $post_data =[], $row_id = '', $action = ''){

        if($row_id){
            $article   = self::where('row_id', $row_id)->first();
            if($article){
                return ['article' => $this->format($article)];
            }
        }

        return [];
    }


    public function load_articles($filters = [], $limit = 10, $page = 1){
        $query = self::with(['author:row_id,name,slug,thumb,profile'])->where('status', self::STATUS_PUBLISH);

        $sortDir = $filters['sort_dir'] ?? 'desc';

        if (!empty($filters['sort_by'])) {
            $query->orderBy($filters['sort_by'], $sortDir);
        }

        $articles = [];

        //$paginator = $query->paginate($limit, ['*'], 'page', $page);
        $paginator = $query->paginate($limit);

        foreach ($paginator->items() as $item) {

            $item = $item->toArray();

            $item['author_name'] = $item['author']['name'] ?? '';
            $item['author_slug'] = $item['author']['username'] ?? '';
            $item['author_thumb'] = $item['author']['thumb'] ?? '';
            $item['author_profile'] = $item['author']['profile'] ?? '';

            unset($item['author']);

            $item = $this->format($item);

            $articles[] = $this->blogs_article_format($item);
        }

        return $articles;
    }

    public function blogs_listing_hook(){
       return self::with(['author:row_id, name, username, image']);
    }

    public function blogs_listing_format($result){

        if(is_array($result)){

            $category_ids = [];

            foreach($result as $row){

                if($row->categories != ''){

                    $categories = explode(',', $row->categories);

                    foreach($categories as $category_id){
                        $category_ids[] = trim($category_id);
                    }
                }
            }

            $categories_map = [];

            $category_ids = array_unique($category_ids);

            if(count($category_ids) > 0){

                $categories_list = CategoriesModel::whereIn('row_id', $category_ids)->get();
                if($categories_list->count()){

                    foreach($categories_list as $category_item){
                        $categories_map[$category_item->row_id] = clean_display($category_item->name);
                    }
                }
            }

            foreach($result as $row){

                if($row->categories != ''){

                    $categories = explode(',', $row->categories);

                    $category_names = [];

                    foreach($categories as $_category_row_id){

                        if(array_key_exists($_category_row_id, $categories_map)){

                            $category_names[] = $categories_map[$_category_row_id];
                        }
                    }

                    $row->category = implode(', ', $category_names);
                }
            }
        }
        
        return $result;
    }

    public function blogs_article_format($row){

        $category_ids = [];
        $category_names = [];
        
        $categories = explode(',', $row->categories);

        foreach($categories as $category_id){

            $category_ids[] = trim($category_id);
        }

        if(count($category_ids) > 0){

            $categories_list = CategoriesModel::whereIn('row_id', $category_ids)->get();

            if($categories_list->count()){

                foreach($categories_list as $category_item){

                    $categories_map[$category_item->row_id] = clean_display($category_item->name);
                    $category_names[] = clean_display($category_item->name);
                }
            }
        }

        $row->_category_names = implode(', ', $category_names);
                    
        return $row;
    }

    public function blogs_related_articles($request){
        $slug = $request->post('slug');
        return self::with(['authorData:row_id,name,slug,thumb,profile'])->where('slug', '!=', $slug)->where('status', self::STATUS_PUBLISH);
    }

    public function statuses(){

        $status = [];

        $status[] = ['key' => self::STATUS_DRAFT, 'value' => 'Draft'];
        $status[] = ['key' => self::STATUS_PUBLISH, 'value' => 'Published'];
        $status[] = ['key' => self::STATUS_DISABLED, 'value' => 'Disabled'];

        return $status;
    }


    public function blog_type_options(){
        $options = [];
        $options[] = ['key' => self::BLOG_TYPE_FEATURE, 'value' => 'Feature Article'];
        $options[] = ['key' => self::BLOG_TYPE_POPULAR, 'value' => 'Popular Article'];
        return $options;
    }

    public function blogs_list($request, $user){
        $query = self::query();

        if (!empty($request->blog_type)) {
            $query->where('blog_type', trim($request->blog_type));
        }

        if (!empty($request->category_id)) {
            $query->where('categories', 'LIKE', '%' . trim($request->category_id) . '%');
        }

        return $query->where('status', 1)
            ->orderBy('id', 'desc');
    }

    public function blog_view($request, $user){
        $validator = Validator::make($request->all(), [
            'slug' => 'required',
        ]);

        if ($validator->fails()) {
            return [
                'status' => false,
                'message' => $validator->errors(),
            ];
        }

        $blog = self::where('slug', $request->slug)->where('status', 1)->first();

        if (!$blog) {
            return [
                'status' => false,
                'message' => 'No records found.',
            ];
        }

        $blog = $this->format($blog);

        return [
            'status' => true,
            'message' => 'View Blog.',
            'records' => $blog,
        ];
    }
}