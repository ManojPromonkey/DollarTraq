<?php

namespace App\Modules\Base\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class BaseModel extends Model{

    protected $table_index = '';
    protected $table_name = '';

    private $_post_data = [];

    public function setTableIndex($table_index){

        $this->table_index = $table_index;
    }

    public function getTableIndex(){

        return $this->table_index;
    }

    public function setTableName($table_name){

        $this->table_name = $table_name;
    }

    public function getTableName(){

        return $this->table_name;
    }

    public function post_save($table = false, $skip_row_id_entry = false){

        if(count($this->_post_data) == 0){

			die("<b>Save post error!!</b> Empty fields!!");
		}	

		$table_name = ($table && !empty($table)) ? $table : $this->getTable();

		$this->validate_post_fields($table_name);

        try {

            /*
            Auto insert row id
            */
			$row_id = '';

            if($skip_row_id_entry == false){

                $row_id = $this->generate_unique_id();

                $this->_post_data[$this->getTableIndex()] = $row_id;
            }

            $id = DB::table($table_name)->insertGetId($this->_post_data);

			$this->_post_data = [];

            return $row_id != '' ? $row_id : $id;
			
        }catch (\Exception $e) {
    
            die($e->getMessage());
        }
	}

	public function post_update($id=false, $key=false, $table=false){
		if(!$id || $id ==''){
			die("<b>Value missing!!</b> id is missing!!");
		}

		$table_name = ($table && !empty($table)) ? $table : $this->getTable();
		$table_fields = $this->validate_post_fields($table_name);
		$update_key = ($key && !empty($key)) ? $key : $this->getTableIndex();

		if($update_key && $update_key != ''){

			//check if key exists
			$fields = $table_fields['fields'];

			if(!in_array($update_key, $fields)){

				die("<b>Key missmatching!!</b><br /> {$update_key} is missing in table {$table_name}!!");
			}
		}

		try {

            /*
            Auto insert row id
            */

			DB::table($table_name)->where($update_key, $id)->update($this->_post_data);

			$this->_post_data = [];

            return $id;
        }catch (\Exception $e) {
    
            die($e->getMessage());
        }
	}

    function fetch_row_by_id($id = NULL){

		if(!is_null($id)){

			return DB::table($this->getTable())->where($this->table_index, '=', $id)->first();
		}	

		return false;	
	}
	
	function fetch_row_by_field($field, $value, $clause='where'){

		if($clause == 'like'){

			return DB::table($this->getTable())->where($field, 'like', '%' . $value . '%')->first();
		
        }elseif($clause=='where'){

			return DB::table($this->getTable())->where($field, '=', $value)->first();
		}		
		return false;	
	}

    public function generate_slug($string = '', $replace='-'){

		$string = strtolower($string);

		$string = trim($string);
		$string = rtrim($string, '-');

		$string = preg_replace("/[^a-z0-9_\s-]/", "", $string);

		$string = preg_replace("/[\s-]+/", " ", $string);

		$string = preg_replace("/[\s_]/", $replace, $string);

		$string = trim($string);
		$string = rtrim($string, '-');

 	   	return $string;
	}	

	public function validate_slug($slug = '', $column = '', $table_name = '', $seperator = '-', $exclude_row_id = '', $index_field = '', $query_filters = []){

		$_slug = $slug;
		$slug_nums = array();

        $slugs = DB::table($this->getTable());
		
		$slugs->where($column, 'like', '%' . addslashes($slug) . '%');

		if($exclude_row_id != '' && $index_field != ''){

			$slugs->where($index_field, '!=', $exclude_row_id);
		}

		if(count($query_filters) > 0){

			foreach($query_filters as $query_filter){

				if($query_filter['key'] == 'where'){

					$slugs->where($query_filter['column'], $query_filter['value']);
				}
			}
		}

        if($slugs->count() > 0){

            if($slugs->count() == 1){

                return $slug.$seperator."2";
            }else{

                $slugs = $slugs->get();

                $max_occurence = 1;

				foreach($slugs as $result){
				
					$__slug = explode("-", $result->{$column});
					$num = $__slug[(count($__slug)-1)];
				
					if(is_numeric($num)){

						$num = (int)$num;

						if($num >= $max_occurence){
							
							$max_occurence = $num;
						}
					}
				}
				
				$slug_suffix = $max_occurence + 1;
				
				return $slug . $seperator . $slug_suffix;
            }

        }else{

            return substr($_slug, 0, 200);
        }
	}

    public function validate_post_fields($table_name){

		//validate fields
		$fields = DB::connection()->getSchemaBuilder()->getColumnListing($table_name);

		$extra_fields = [];

		foreach($this->_post_data as $post_key => $post_value){
			
            if(!in_array($post_key, $fields)){

				$extra_fields[] = $post_key;
			}
		}

		return ['status' => true, 'fields' => $fields];

		if(count($extra_fields) > 0){

			die("<b>Table fields missmatching!!</b> <br />Following fields are missing in table: ".implode("<br />", $extra_fields));
		}
	}

    public function generate_unique_id($key = ''){

		$table_name = $this->getTable();

		if($table_name && $table_name !== ''){

            $id = DB::select("SHOW TABLE STATUS LIKE '".$table_name."'");

            $next_increment = $id[0]->Auto_increment;

			$po_unq_id_1 = random_string_generator(14);
			$po_unq_id_2 = random_string_generator(17);
			$po_unq_id_3 = random_string_generator(9);

			if($key == ''){

				$key = $table_name;
			}

			return md5($po_unq_id_1 . '-' . $next_increment . $po_unq_id_2 . '-' . $key . date("dYmHis") . $po_unq_id_3);
		}

		die("<b>Table name missmatching!!");
	}

    public function set_post_data($key = false, $value = false){
		
		if(is_array($value)){
        
			$this->_post_data[$key] = json_encode($value);
		}else{

			$this->_post_data[$key] = ($value);
		}
	}

    public function get_post_data($key = false){

		if($key && $key != ''){

			if(array_key_exists($key, $this->_post_data)){

				return $this->_post_data[$key];
			}

			return false;

		}else{

			return $this->_post_data;
		}
	}

	public function key_filter($array = [], $key = '', $key_field = 'key', $return_row = false){

		foreach($array as $row){

			if($row[$key_field] == $key){

				if($return_row){

					return $row;
				}

				return $row['value'];
			}
		}

		return false;
	}

	public function getTableSchema(){

		$_fields = [];

		$primary = '';

		$indexes = Schema::getIndexes($this->getTable());

		foreach($indexes as $index){

			if($index['primary'] == 1){

				$primary = $index['columns'][0];
			}
		}

		$table_fields = DB::connection()->getSchemaBuilder()->getColumnListing($this->getTable());

		foreach($table_fields as $table_field){

			if($table_field == $primary){

				$_fields[$table_field] = 'primary';
			}else{
			
				$_table_field_type = Schema::getColumnType($this->getTable(), $table_field);

				$_fields[$table_field] = $_table_field_type;
			}
		}

		return $_fields;
	}

	public function base_url($url = ''){

		$base_url = config('app.web_url');

		if($url && $url != ''){

			$base_url = rtrim($base_url, '/') . '/' . trim(ltrim($url, '/'));
		}

		return $base_url;
	}

	public function file_url($value = '', $path = ''){

		if($value != ''){

			return URL::to(Storage::url($path . clean_display($value)));
		}

		return '';
	}

	public function format_date($value = '', $format = 'date'){

		if($value){

			if($format == 'datetime'){

				return date("d M Y h:i A", strtotime($value));
			}

			if($format == 'date'){

				return date("d M Y", strtotime($value));
			}

			if($format == 'time'){

				return date("h:i A", strtotime($value));
			}
		}
	}
}