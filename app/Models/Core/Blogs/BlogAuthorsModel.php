<?php
namespace App\Models\Core\Blogs;

use App\Core\CoreModel;

class BlogAuthorsModel extends CoreModel
{
    protected $table = 'blog_authors';

    function __construct(){
        
        $this->setTableIndex('row_id');
    }

    public function format($row = false){
        return $row;
    }
}