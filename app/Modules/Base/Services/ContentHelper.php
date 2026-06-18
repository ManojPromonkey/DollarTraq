<?php
namespace App\Modules\Base\Services;

use Illuminate\Support\Facades\DB;

class ContentHelper {

    function generate_slug($string = '', $replace='-'){

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

	function validate_slug($slug = '', $column = '', $table_name = '', $seperator = '-', $exclude_row_id = '', $index_field = ''){

		$_slug = $slug;
		$slug_nums = array();

        $slugs = DB::table($this->getTable());
		
		$slugs->where($column, 'like', '%' . addslashes($slug) . '%');

		if($exclude_row_id != '' && $index_field != ''){

			$slugs->where($index_field, '!=', $exclude_row_id);
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

    function random_string($length = 10, $type = ''){
		
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		if($type == 'numeric'){

			$characters = '012345678901234567890123456789012345678901234567890123456789';
		}

		$charactersLength = strlen($characters);
		$randomString = '';
		
        for($i = 0; $i < $length; $i++){

			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		
		return $randomString;
	}

    function text_truncate_center($string, $limit = 20, $end = '...'){

		if(strlen($string) <= $limit){

        	return $string;
    	}

		$separatorLen = strlen($end);
		$targetLen = $limit - $separatorLen;
		$startLen = ceil($targetLen / 2);
		$endLen = floor($targetLen / 2);

		return substr($string, 0, $startLen) . $end . substr($string, -$endLen);
	}

	function text_avatar($string = '', $length = 2){

		$short_avatar = '';

		if($string != ''){
		
			$string = explode(' ', $string);

			for($n = 0; $n < $length; $n++){

				if(array_key_exists($n, $string)){

					$short_avatar .= strtoupper(substr(trim($string[$n]), 0, 1));
				}
			}
		}

		return $short_avatar;
	}
}
