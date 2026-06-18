<?php

namespace App\Modules\Base\Controllers\Factory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;

use App\Modules\Base\Services\FileHelper;

use Mews\Purifier\Facades\Purifier;

use Illuminate\Support\Facades\Log;

class HandleController extends Controller
{

    protected function listing($factory_config = [], $request){

        $account_token = false;

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);

        //return ['status' => false, 'code' => 'no_account 2', 'authenticated' => $authenticated , 'user' => $user];

        if($authenticated){

            $primary_model = $this->load_model($factory_config);

            if($primary_model != false){

                $perPage = $request->post('per_page', 10);
                $page = $request->post('page', 1);

                /*
				DB query
				*/

                DB::enableQueryLog();

                if(array_key_exists('query_method', $factory_config)){

					if(is_array($factory_config['query_method']) && array_key_exists('model', $factory_config['query_method']) && array_key_exists('method', $factory_config['query_method'])){

						$query_method_model = $this->load_model($factory_config['query_method']);

						if(method_exists($query_method_model, $factory_config['query_method']['method'])){

							$query = $query_method_model->{$factory_config['query_method']['method']}($request, $user);

						}else{
						
                            return ['status' => false, 'message' => 'Query method not exists in the model!'];
						}
					}else{

						return ['status' => false, 'message' => 'Query method not exists in the model!'];
					}
				}else{

                    $query = $primary_model::query();
                }

                $filters = $request->post('filters');

                if($filters){

                    if(is_string($filters)){
            
                        $filters = json_decode($filters, true);
                    }

                    if(is_array($filters) && count($filters)){
            
                        foreach($filters as $filter_key => $filter_value){
                
                            $query->where($filter_key, $filter_value);
                        }
                    }
                }

                $search = $request->post('search');

                if($search){

                    $search = @json_decode(stripslashes($search), true);

                    if(is_array($search) && count($search) > 0){

                        $allowedSearchTypes = ['match', 'like', 'gt', 'lt', 'date', 'date_range', 'dategt', 'datelt'];

                        foreach($search as $searchField => $searchKeywords){

                            $searchType = 'like';

                            if(isset($searchKeywords['type']) && in_array($searchKeywords['type'], $allowedSearchTypes)){

                                $searchType = trim($searchKeywords['type']);
                            }

                            switch($searchType){
                                case 'gt':
                                    $query->where($searchField, '>', trim($searchKeywords['keyword']));
                                    break;

                                case 'lt':
                                    $query->where($searchField, '<', trim($searchKeywords['keyword']));
                                    break;

                                case 'like':
                                    $query->where($searchField, 'LIKE', '%' . trim($searchKeywords['keyword']) . '%');
                                    break;

                                case 'date':
                                    $date = date('Y-m-d', strtotime(trim($searchKeywords['keyword'])));
                                    $query->whereDate($searchField, '=', $date);
                                    break;

                                case 'date_range':
                                    $from = date('Y-m-d', strtotime(trim($searchKeywords['keyword'][0])));
                                    $to = date('Y-m-d', strtotime(trim($searchKeywords['keyword'][1])));
                                    $query->whereBetween(DB::raw("DATE($searchField)"), [$from, $to]);
                                    break;

                                case 'dategt':
                                    $date = date('Y-m-d', strtotime(trim($searchKeywords['keyword'])));
                                    $query->whereDate($searchField, '>=', $date);
                                    break;

                                case 'datelt':
                                    $date = date('Y-m-d', strtotime(trim($searchKeywords['keyword'])));
                                    $query->whereDate($searchField, '<=', $date);
                                    break;

                                case 'match':
                                    $query->where($searchField, '=', trim($searchKeywords['keyword']));
                                    break;

                                default:
                                    $query->where($searchField, trim($searchKeywords['keyword']));
                                    break;
                            }
                        }
                    }
                }

                $paginator = $query->paginate($perPage, ['*'], 'page', $page);

                // dd(DB::getQueryLog());
                
                $_items = [];

                /*
                Format if function available
                */

                $items = $paginator->items();

                if(method_exists($primary_model, 'format')){

                    foreach($items as $item){

                        $_items[] = $primary_model->format($item);
                    }
                }else{

                    $_items = $items;
                }

                if(array_key_exists('after_result_method', $factory_config)){

					if(is_array($factory_config['after_result_method']) && array_key_exists('model', $factory_config['after_result_method']) && array_key_exists('method', $factory_config['after_result_method'])){

						$after_result_method_model = $this->load_model($factory_config['after_result_method']);

						if(method_exists($after_result_method_model, $factory_config['after_result_method']['method'])){

							$_items = $after_result_method_model->{$factory_config['after_result_method']['method']}($_items, $user);

						}else{
						
                            return ['status' => false, 'message' => 'After result method not exists in the model!'];
						}
					}else{

						return ['status' => false, 'message' => 'After result model not exits!'];
					}
                }

                return [
                    'status' => true,
                    'total' => $paginator->total(),
                    'page' => $paginator->currentPage(),
                    'per_page' => $perPage,
                    'records' => $_items
                ];
            }else{

                return ['status' => true, 'total' => 0, 'page' => 0, 'per_page' => 0, 'records' => []];
            }
        }else{

            return ['status'=>false, 'no_account' => true];
        }
    }

    protected function save($factory_config = [], $request){

        

        ini_set('memory_limit', '512M');

		$account_token = false;

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);
        
        if($authenticated){

            if($request->isJson()){

                $post = $request->post();
            }else{
            
                $post = $request->input();
            }
            
            $post_request = $request;

            //dd($post);

            $primary_model = $this->load_model($factory_config);

            if($primary_model != false){

                /*
                Save process
                */
                {

                    $action = 'save';

                    $table_name = $primary_model->getTable();
                    $table_index = $primary_model->getTableIndex();

                    /*
                    Check if the model index key exists in the model and post request
                    */

                    if(isset($post[$table_index]) && $post[$table_index] != ''){

                        $action = 'update';

                        $row = $primary_model->fetch_row_by_id($post[$table_index]);

                        if($row == false){
                            return ['status' => false, 'message' => 'Entry not found!'];
                        }
                    }

                    $custom_row_id_exists = false;

                    $fields = [];

                    /*
                    Load table fields
                    */
                    $table_fields = $primary_model->getTableSchema();

                    $extra_fields = [];

                    foreach($table_fields as $table_field_name => $table_field_type){

                        $fields[$table_field_name] = ['datatype' => $table_field_type, 'ai' => $table_field_type == 'primary' ? true : false];

                        if($table_field_name == $table_index && $table_field_type == 'varchar'){

                            $custom_row_id_exists = true;
                        }

                        /*
                        Prefill fields
                        */

                        if(($table_field_type == 'varchar' || $table_field_type == 'longtext') && $table_field_name != $table_index){

                            if(array_key_exists($table_field_name, $post)){

                                if($post[$table_field_name] == ''){

                                    $extra_fields[$table_field_name] = '';
                                }
                            }else{

                                if($action == 'save'){
                                
                                    $extra_fields[$table_field_name] = '';
                                }

                                if($action == 'update'){

                                    if($row->{$table_field_name} == ''){

                                        $extra_fields[$table_field_name] = '';
                                    }
                                }
                            }
                        }

                        if($table_field_type == 'int' || $table_field_type == 'tinyint'){

                            if(array_key_exists($table_field_name, $post)){

                                if($post[$table_field_name] == ''){

                                    $extra_fields[$table_field_name] = 0;
                                }
                            }else{

                                if($action == 'save'){

                                    $extra_fields[$table_field_name] = 0;
                                }

                                if($action == 'update'){

                                    if($row->{$table_field_name} == '' || $row->{$table_field_name} == 0){

                                        $extra_fields[$table_field_name] = 0;
                                    }
                                }
                            }
                        }
                    }

                    $request->merge($extra_fields);

                    /*
                    Important
                    */

                    if($request->isJson()){

                        $post = $request->post();
                    }else{
            
                        $post = $request->input();
                    }

                    /*
                    Check if fields function available and override the table fields
                    */
                    // if(method_exists($primary_model, 'fields')){

                    //     $_fields = $this->{$primary_model}->fields();

                    //     foreach($_fields as $_field_index => $_field){

                    //         $fields[$_field_index] = $_field;
                    //     }
                    // }

                    /*
                    Before save/update callback
                    */

                    if(array_key_exists('before_callback', $factory_config)){

                        $callback_method = array_key_exists('method', $factory_config['before_callback']) ? $factory_config['before_callback']['method'] : '';

                        if($callback_method != ''){

                            $callback_model = $this->load_model($factory_config['before_callback']);

                            if($callback_model != false){

                                if(method_exists($callback_model, $callback_method)){

                                    $_row_id = array_key_exists($table_index, $post) ? $post[$table_index] : '';

                                    $before_response = $callback_model->{$callback_method}($post, $action, $fields, $user, $account_token, $_row_id, $request);

                                    if(is_array($before_response) && count($before_response) > 0){

                                        $_before_response_fields = [];

                                        foreach($before_response as $key => $value){

                                            $_before_response_fields[$key] = $value;
                                        }

                                        $request->merge($_before_response_fields);

                                        if($request->isJson()){

                                            $post = $request->post();
                                        }else{
                                
                                            $post = $request->input();
                                        }
                                    }
                                }
                            }
                        }
                    }

                    /*
                    Prepare save
                    */
                    foreach($post as $input_key => $post_value){

                        if(array_key_exists($input_key, $fields) && $input_key != $table_index){

                            $_field = $fields[$input_key];

                            $db_field = $input_key;
                            $post_key = $input_key;

                            /*
                            Check if field mapping declared
                            */
                            if(array_key_exists('map', $fields[$input_key])){

                                $db_field = $fields[$input_key]['map'];
                            }

                            if($_field['datatype'] == 'date'){

                                if($post_value !== ''){

                                    $post_value = date('Y-m-d', strtotime($request->post($post_key)));

                                    $primary_model->set_post_data($db_field, $post_value);
                                }
                            }elseif($_field['datatype'] == 'datetime'){

                                if($post_value !== ''){

                                    $post_value = date('Y-m-d H:i:s', strtotime($request->post($post_key)));

                                    $primary_model->set_post_data($db_field, $post_value);
                                }
                            }else{

                                $post_value = $request->post($post_key);

                                if($_field['datatype'] == 'text' || $_field['datatype'] == 'tinytext' || $_field['datatype'] == 'mediumtext' || $_field['datatype'] == 'longtext'){

                                    if(gettype($post_value) == 'string'){
                                    
                                        $post_value = Purifier::clean($post_value);
                                    }
                                }

                                $primary_model->set_post_data($db_field, $post_value);
                            }
                        }
                    }

                    $post_data = [];

                    try {

                        /*
                        Upload images
                        */

                        $file_helper = new FileHelper;

                        $files = $request->files->all();

                        if(!empty($files) && is_array($files)){
    
                            $files_to_upload = count($files);
                            $uploadedFiles = [];

                            foreach($files as $fieldName => $file){

                                $config = [];

                                if($request->input($fieldName . '_path') != ''){
                                
                                    $config['upload_directory'] = $request->input($fieldName . '_path');
                                }

                                if($request->input($fieldName . '_fix_size') != ''){
                                
                                    $config['sizes'] = $request->input($fieldName . '_fix_size');
                                }

                                $uploaded_file = $file_helper->upload_file($file, $config);

                                if($uploaded_file->status){

                                    $uploadedFiles[$fieldName] = $uploaded_file->upload_path;
                                }
                            }

                            if(count($uploadedFiles) != $files_to_upload){

                                return [
                                    'status' => false,
                                    'message' => 'There was an error while uploading the files. Please try again later.'
                                ];
                            }

                            $request->merge($uploadedFiles);

                            // $post = $request->post();
                            if($request->isJson()){

                                $post = $request->post();
                            }else{
                    
                                $post = $request->input();
                            }

                            foreach($uploadedFiles as $file_name => $file_value){

                                $primary_model->set_post_data($file_name, $file_value);
                            }
                        }

                        if($action == 'save'){
                        
                            $message = 'Information added successfully.';

                            if(array_key_exists('add_success_message', $factory_config)){

                                $message = $factory_config['add_success_message'];
                            }

                            if($custom_row_id_exists){

                                /*
                                Auto add added on
                                */
                                if(array_key_exists('added_on', $fields) && !isset($post['added_on'])){

                                    $primary_model->set_post_data('added_on', date('Y-m-d H:i:s'));
                                }

                                if(array_key_exists('created_at', $fields) && !isset($post['created_at'])){

                                    $primary_model->set_post_data('created_at', date('Y-m-d H:i:s'));
                                }
                            
                                /*
                                Generate row id
                                */
                                // $row_id = $this->{$primary_model}->generate_unique_id($primary_model);

                                // $this->{$primary_model}->set_post_data($table_index, $row_id);
                            }

                            $post_data = $primary_model->get_post_data();

                            $row_id = $primary_model->post_save();
                        }else{

                            $row_id = $request->post($table_index);

                            $message = 'Information updated successfully.';

                            if(array_key_exists('update_success_message', $factory_config)){

                                $message = $factory_config['update_success_message'];
                            }

                            /*
                            Auto add updated on
                            */
                            if(array_key_exists('updated_on', $fields)){

                                $primary_model->set_post_data('updated_on', date('Y-m-d H:i:s'));
                            }

                            if(array_key_exists('updated_at', $fields)){

                                $primary_model->set_post_data('updated_at', date('Y-m-d H:i:s'));
                            }

                            $post_data = $primary_model->get_post_data();

                            $primary_model->post_update($row_id);
                        }

                        /*
                        Post save/update callback
                        */

                        $_return = ['status' => true, 'message' => $message, 'row_id' => $row_id];

                        if(array_key_exists('after_callback', $factory_config)){

                            $after_callback_method = array_key_exists('method', $factory_config['after_callback']) ? $factory_config['after_callback']['method'] : '';

                            if($after_callback_method != ''){

                                $after_callback_model = $this->load_model($factory_config['after_callback']);

                                if($after_callback_model != false){

                                    if(method_exists($after_callback_model, $after_callback_method)){

                                        $after_callback_return = $after_callback_model->{$after_callback_method}($request, $post_data, $row_id, $action, $user);

                                        if(is_array($after_callback_return)){

                                            $_return = array_merge($_return, $after_callback_return);
                                        }
                                    }
                                }
                            }
                        }

                        return $_return;

                    }catch (\Exception $e) {

                        return ['status' => false, 'code' => $e->getMessage()];
                    }
                }
            }else{

                return ['status' => false, 'message' => 'Factory config missing!'];
            }
        }else{

            return ['status' => false, 'code' => 'no_account 1'];
        }
    }

    protected function loadSingle($factory_config = [], $request){

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);
        
        if($authenticated){

            $post = $request->post();

            $primary_model = $this->load_model($factory_config);

            if($primary_model != false){

                $table_name = $primary_model->getTable();
                $row_id = $primary_model->getTableIndex();

                $db_field = $row_id;

                if(array_key_exists('id', $factory_config)){

					$row_info = $factory_config['id'];

                    if(is_string($row_info)){

						$row_id = $row_info;
						$db_field = $row_info;
					}else{

						$row_id = $row_info['input'];
						$db_field = $row_info['field'];
					}
				}

                $row_id_value = $request->post($row_id);

				if($row_id_value != ''){

					/*
					Check if format function available
					*/
					$format_method = false;

					if(method_exists($primary_model, 'format')){

						$format_method = true;
					}

					$row = false;

					if(array_key_exists('method', $factory_config)){

						$data_model = $this->load_model($factory_config['method']);

                        if($data_model != false){

						    $row = $data_model->{$factory_config['method']['method']}($db_field, trim($row_id_value));
                        }
						
					}else{

                        $row_query = $primary_model->fetch_row_by_field($db_field, trim($row_id_value));

						if($row_query != false){

							$row = $row_query;
						}
					}

					if($row){

						if($format_method){

							$row = $primary_model->format($row, 'single');
						}

						$row = $this->filter_data($row, $factory_config);

						/*
						Added on / Updted on formatted
						*/
						if(property_exists($row, 'added_on')){

                            if($row->added_on){

    							$row->added_on_formatted = date("d M Y, h:i A", strtotime($row->added_on));
                            }else{

                                $row->added_on = '';
                            }
						}

						if(property_exists($row, 'created_at')){

                            if($row->created_at){
							
                                $row->created_at_formatted = date("d M Y, h:i A", strtotime($row->created_at));
                            }else{

                                $row->created_at = '';
                            }
						}

						if(property_exists($row, 'updated_on')){

                            if($row->updated_on){

							    $row->updated_on_formatted = date("d M Y, h:i A", strtotime($row->updated_on));
                            }else{

                                $row->updated_on = '';
                            }
						}

						if(property_exists($row, 'updated_at')){

                            if($row->updated_at){
							
                                $row->updated_at_formatted = date("d M Y, h:i A", strtotime($row->updated_at));
                            }else{

                                $row->updated_at = '';
                            }
						}

                        if(array_key_exists('after_result_method', $factory_config)){

                            if(is_array($factory_config['after_result_method']) && array_key_exists('model', $factory_config['after_result_method']) && array_key_exists('method', $factory_config['after_result_method'])){

                                $after_result_method_model = $this->load_model($factory_config['after_result_method']);

                                if(method_exists($after_result_method_model, $factory_config['after_result_method']['method'])){

                                    $row = $after_result_method_model->{$factory_config['after_result_method']['method']}($row, $user);

                                }else{
                                    return ['status' => false, 'message' => 'After result method not exists in the model!'];
                                }
                            }else{

                                return ['status' => false, 'message' => 'After result model not exits!'];
                            }
                        }

						return ['status' => true, 'data' => $row];
					}else{

						return ['status' => true, 'data' => false];
					}

				}else{

					return ['status' => false, 'message' => 'Input missing!'];
				}
            }else{

                return ['status' => false, 'message' => 'Input missing!'];
            }
        }else{

            return ['status' => false, 'code' => 'no_account 2'];
        }
	}

    public function validateUnique($factory_config = [], $request){

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);

        if($authenticated){

            $post = $request->post();
            //Log::debug('Request data', request()->all());                          
            $primary_model = $this->load_model($factory_config);

            if($primary_model != false){

                if(array_key_exists('field', $factory_config)){

                    $field = $factory_config['field'];

                    if(is_array($field)){

                        $post_field = $field['key'];
                        $db_field = $field['field'];
                    }else{

                        $post_field = $field;
                        $db_field = $field;
                    }
                    
                    $value = '';
                    if(isset($post['value'])){
                        $value = $post['value'];
                    }

                    if(isset($post[$db_field])){
                        $value = $post[$db_field];
                    }

                    if($value != ''){
                        /*
                        Check if row id exists in post request
                        */
                        $post_row_id_key = $factory_config['row_id'];

                        $query = DB::table($primary_model->getTable());

                        if(isset($post[$post_row_id_key])){

                            $query->where($post_row_id_key, '!=', $post[$post_row_id_key]);
                        }

                        $row = $query->where($db_field, trim($value))->get();

                        if($row->count() > 0){

                            return ['status' => true, 'code' => 'd'];
                        }else{

                            return ['status' => true, 'code' => 'u'];
                        }
                    }else{

                        return ['status' => true, 'code' => 'empty_input'];
                    }
                }else{

                    return ['status' => false, 'message' => 'Input missing!'];
                }
            }else{

                return ['status' => false, 'message' => 'Input missing!'];
            }
        }else{

            return ['status' => false, 'code' => 'no_account 3'];
        }
	}

    protected function customRequest($factory_config = [], $request){

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);
        //dd($user);
        if($authenticated){

            if(array_key_exists('model', $factory_config)){

                $primary_model = $this->load_model($factory_config['model']);

                if($primary_model != false){
                
                    if(array_key_exists('method', $factory_config['model'])){

                        $method = $factory_config['model']['method'];
                        //echo $method;

                        if(method_exists($primary_model, $method)){

                            try{
                            
                                return $primary_model->{$method}($request, $user);
                            }catch(\Exception $e){

                                print_r($e->getMessage());
                            }
                        }else{

                            return ['status' => false, 'message' => 'Input missing!'];
                        }
                    }else{

                        return ['status' => false, 'message' => 'Input missing!'];
                    }
                }else{

                    return ['status' => false, 'message' => 'Input missing!'];
                }
            }else{

                return ['status' => false, 'message' => 'Model declaration missing!'];
            }
        }else{

            return ['status' => false, 'code' => 'no_account 4'];
        }
    }

    protected function remove($factory_config = [], $request){

		$account_token = false;

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);

        if($authenticated){

            $post = $request->post();

            $primary_model = $this->load_model($factory_config);

            if($primary_model != false){

                /*
				Input identifier
				*/

				if(array_key_exists('id', $factory_config)){

					$row_info = $factory_config['id'];

					if(is_string($row_info)){

						$row_id = $row_info;
						$db_field = $row_info;
					}else{

						$row_id = $row_info['input'];
						$db_field = $row_info['field'];
					}
				}

                $row_id_value = $post[$row_id];

				$_row_id_value = [];
				
				if(is_array($row_info) && array_key_exists('json', $row_info)){

					$_row_id_value = @json_decode($row_id_value, true);

					if(is_array($_row_id_value) && count($_row_id_value) > 0){

						$_row_id_values = [];

						foreach($_row_id_value as $__row_id_value){

							if($__row_id_value != ''){

								$_row_id_values[] = trim($__row_id_value);
							}
						}

						$_row_id_value = $_row_id_values;
					}
				}else{

					$_row_id_value[] = $row_id_value;
				}

                if(is_array($_row_id_value) && count($_row_id_value) > 0){

					DB::beginTransaction();

                    try{

                        if(array_key_exists('before_callback', $factory_config)){

                            $before_callback_method = array_key_exists('method', $factory_config['before_callback']) ? $factory_config['before_callback']['method'] : '';

                            if($before_callback_method != ''){

                                $before_callback_model = $this->load_model($factory_config['before_callback']);

                                if($before_callback_model != false){

                                    if(method_exists($before_callback_model, $before_callback_method)){

                                        $_before_callback_return = $before_callback_model->{$before_callback_method}($db_field, $_row_id_value, $row_id, $user, $request);

                                        if(is_array($_before_callback_return) && (array_key_exists('status', $_before_callback_return) && $_before_callback_return['status'] == false)){

                                            return $_before_callback_return;
                                        }
                                    }
                                }
                            }
                        }

                        $entries = [];

                        if(array_key_exists('after_callback', $factory_config)){

                            $entries = DB::table($primary_model->getTable())->whereIn($db_field, $_row_id_value)->get();
                         }

                        DB::table($primary_model->getTable())->whereIn($db_field, $_row_id_value)->delete();
					
						$message = 'Entry deleted successfully.';

						/*
						Post delete callback
						*/

                        if(array_key_exists('success_message', $factory_config)){

							$message = $factory_config['success_message'];
						}
                        
                        $return = ['status' => true, 'message' => $message];

                        if(array_key_exists('after_callback', $factory_config)){

                            $after_callback_method = array_key_exists('method', $factory_config['after_callback']) ? $factory_config['after_callback']['method'] : '';

                            if($after_callback_method != ''){

                                $after_callback_model = $this->load_model($factory_config['after_callback']);

                                if($after_callback_model != false){

                                    if(method_exists($after_callback_model, $after_callback_method)){

                                        $_after_callback_returned = $after_callback_model->{$after_callback_method}($entries, $db_field, $_row_id_value, $row_id, $user);

                                        if(is_array($_after_callback_returned)){

                                            $return = array_merge($return, $_after_callback_returned);
                                        }
                                    }
                                }
                            }
                        }

                        DB::commit();

                        return $return;

					}catch(\Exception $e){

						DB::rollback();
						return ['status' => false, 'message' => "There was an error while processing your request."];
					}
					
				}else{

					$this->response(['status' => false, 'message' => "Input missing."]);
				}
            }else{

                return ['status' => false, 'message' => 'Input missing!'];
            }
        }else{

            return ['status' => false, 'code' => 'no_account 5'];
        }
    }
    
    protected function generateDatasets($factory_config = [], $request){
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);
        

		if(array_key_exists('models', $factory_config)){

			$dataset_models = $factory_config['models'];

			$return = [];
			$return['status'] = true;

			foreach($dataset_models as $key_var => $_dataset_models){

				$model_path = $_dataset_models['model'];

				$_model = $model_path;

                $modelInstance = new $_model();

				/*
				Declare primary model
				*/

				$_dataset = [];
                
                if(array_key_exists('method', $_dataset_models)){
                    $_dataset = $modelInstance->{$_dataset_models['method']}($request, $user);
                }else{

                    $results = $modelInstance->get();
                    
                    if(count($results) > 0){

                        foreach($results as $dataset_query_row){

                            if($dataset_query_row->{$_dataset_models['value']}!=''){

                                $_data_row = ['key' => $dataset_query_row->{$_dataset_models['key']}, 'value' => ucwords(clean_display($dataset_query_row->{$_dataset_models['value']}))];

                                if(array_key_exists('additionals', $_dataset_models) && is_array($_dataset_models['additionals'])){

                                    foreach($_dataset_models['additionals'] as $additionals){

                                        $_data_row[$additionals] = clean_display($dataset_query_row->{$additionals});
                                    }
                                }

                                $_dataset[] = $_data_row;
                            }
                        }
                    }
                }

                $return[$key_var] = $_dataset;
			}

            return $return;
		}else{

			return ['status' => false, 'message' => 'Models missing in factory config.'];
		}
	}

    protected function datasets($factory_config = [], $request){

        return $this->generateDatasets($factory_config, $request);
	}

    protected function fileUpload($factory_config = [], $request){

        ini_set('memory_limit', '512M');

		$account_token = false;

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);

        if($authenticated){

            try {

                $file_data = [];

                $_file_input = 'file';

                if(array_key_exists('file', $factory_config)){

                    $_file_input = $factory_config['file'];
                }
                
                // if (!$request->hasFile('profile_pic')) {

                //     return response()->json([
                //         'status' => false,
                //         'message' => 'Profile picture missing.',
                //         'request' => $request
                //     ]);
                // }
                $file = $request->file($_file_input);

                $file_original_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

                $extension = strtolower($file->getClientOriginalExtension());

                $filename = md5(now() . '-' . $file_original_name . '-' . random_string_generator(20)) . '.' . $extension;

                $file_data['original_name'] = $file_original_name;
                $file_data['extension'] = $extension;

                $directory_path = strtolower(random_string_generator(3)) . "/" . strtolower(random_string_generator(3));

                $upload_directory = '';

                if($request->input('upload_dir') != ''){

                    $upload_directory = rtrim(ltrim($request->input('upload_dir'), "/"), "/") . "/";
                }

                if(array_key_exists('path', $factory_config)){

                    $upload_directory = rtrim(ltrim($factory_config['path'], "/"), "/") . "/";
                }

                $file_data['upload_directory'] = $upload_directory;

                $storage_path = "uploads/" . $upload_directory . $directory_path . "/" . $filename;

                $file_data['upload_path'] = $directory_path . "/" . $filename;

                if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){

                    $image = Image::read($file->getRealPath());

                    $fixSizeField = 'sizes';

                    if($request->has($fixSizeField) && $request->{$fixSizeField} != ''){

                        $image = encodeToTargetSize($image, 'jpg', $request->{$fixSizeField} * 1024);

                        Storage::put($storage_path, $image);

                    }else{

                        Storage::put($storage_path, (string) $image->encodeByExtension($extension, 80));
                    }

                }else{

                    Storage::put($storage_path, file_get_contents($file->getRealPath()));
                }

                $file_data['storage_path'] = $storage_path;

                $media_url = URL::to(Storage::url("/"));

                $message = "File uploaded successfully.";

                if(array_key_exists('success_message', $factory_config)){

                    $message = $factory_config['success_message'];
                }

                $return = ['status' => true, 'message' => $message, 'file_path' => $directory_path . "/" . $filename, 'upload_path' => $storage_path, 'media_url' => $media_url, 'file_name' => $file_original_name];

                if(array_key_exists('after_callback', $factory_config)){

                    $after_callback_method = array_key_exists('method', $factory_config['after_callback']) ? $factory_config['after_callback']['method'] : '';

                    if($after_callback_method != ''){

                        $after_callback_model = $this->load_model($factory_config['after_callback']);

                        if($after_callback_model != false){

                            if(method_exists($after_callback_model, $after_callback_method)){

                                $_after_callback_returned = $after_callback_model->{$after_callback_method}($request, $user, $file_data);

                                if(is_array($_after_callback_returned)){

                                    $return = array_merge($return, $_after_callback_returned);
                                }
                            }
                        }
                    }
                }

                return $return;

            }catch (\Exception $e) {

                return ['status' => false, 'code' => $e->getMessage()];
            }
        }else{

            return ['status' => false, 'code' => 'no_account 6'];
        }
    }

    protected function fileRemove($factory_config = [], $request){

        /*
        Authenticate the data if auth key exists
        */
        list($authenticated, $user) = $this->authenticateRequest($factory_config, $request);

        if($authenticated){

            $file = $request->post('file');

            if($file){

                if(array_key_exists('before_remove', $factory_config)){

                    $before_remove_method = array_key_exists('method', $factory_config['before_remove']) ? $factory_config['before_remove']['method'] : '';

                    if($before_remove_method != ''){

                        $before_remove_model = $this->load_model($factory_config['before_remove']);

                        if($before_remove_model != false){

                            if(method_exists($before_remove_model, $before_remove_method)){

                                $return = $before_remove_model->{$before_remove_method}($file, $request, $user);

                                if($return){

                                    try{

                                        if(Storage::disk('public')->exists($file)){

                                            Storage::disk('public')->delete($file);

                                            return ['status' => true, 'message' => 'File removed successfully.'];
                                        }else{

                                            return ['status' => false, 'message' => 'File not found.'];
                                        }
                                    
                                    }catch(\Exception $e){

                                        return ['status' => false, 'message' => $e->getMessage()];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return ['status' => false, 'message' => 'File not found.'];
        }else{

            return ['status' => false, 'code' => 'no_account 7'];
        }
        
    }

    public function handler(Request $request){

        $configs = $this->get_config($request->segments());

        if(array_key_exists('configs', $configs) && count($configs['configs']) > 0){

            $factory_config = $configs['configs'];

            $type = 'list';

            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'save'){

                $type = 'save';
            }

            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'datasets'){

                $type = 'datasets';
            }
            
            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'unique'){

                $type = 'unique';
            }

            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'single'){

                $type = 'single';
            }

            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'delete'){

                $type = 'delete';
            }

            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'custom'){

                $type = 'custom';
            }

            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'upload'){

                $type = 'upload';
            }

            if(array_key_exists('type', $factory_config) && $factory_config['type'] == 'unlink'){

                $type = 'unlink';
            }

            /*
            Load function
            */
            if($type == 'datasets'){

                $return = $this->datasets($configs['configs'], $request);
            }

            if($type == 'save'){

                $return = $this->save($configs['configs'], $request);
            }

            if($type == 'list'){

                $return = $this->listing($configs['configs'], $request);
            }

            if($type == 'single'){

                $return = $this->loadSingle($configs['configs'], $request);
            }

            if($type == 'delete'){

                $return = $this->remove($configs['configs'], $request);
            }

            if($type == 'unique'){

                $return = $this->validateUnique($configs['configs'], $request);
            }

            if($type == 'custom'){

                $return = $this->customRequest($configs['configs'], $request);
            }

            if($type == 'upload'){

                $return = $this->fileUpload($configs['configs'], $request);
            }

            if($type == 'unlink'){

                $return = $this->fileRemove($configs['configs'], $request);
            }

            return response()->json($return, 200);
        }else{

            return response()->json(['status' => false, 'message' => 'Factory config missing.'], 200);
        }
    }

    protected function get_config($request){

        $config_key = [];

        foreach($request as $_request){

            if($_request != 'api' && $_request != 'handle'){

                $config_key[] = $_request;
            }
        }

        $config_key = implode('/', $config_key);

        if($config_key != ''){

            $configs = config("app_handle.{$config_key}");

            if(is_array($configs)){

                return ['factory_config_key' => $config_key, 'configs' => $configs];
            }else{

                return ['error' => true, 'message' => 'Config missing or api key is wrong.'];
            }
        }

        return [];
    }

    protected function load_model($factory_config = [], $model_name = 'model'){

        if(array_key_exists($model_name, $factory_config)){
        //echo     $model_name;
        //dd( $factory_config);
            $primary_model_path = trim($factory_config[$model_name]);
        
			/*
			Load model
			*/
			return new $primary_model_path();
		}

		return false;
    }

    protected function filter_data($data_row, $factory_config){

		if(count((array)$data_row) > 0){

			if(array_key_exists('fields', $factory_config) && array_key_exists('exclude', $factory_config['fields'])){

				$exclude_fields = $factory_config['fields']['exclude'];

				if(count($exclude_fields) > 0){

					foreach($exclude_fields as $exclude_field){

						if(property_exists($data_row, $exclude_field)){

							unset($data_row->{$exclude_field});
						}
					}
				}
			}
		}

		return $data_row;
	}

    protected function authenticateRequest($factory_config = [], $request){

        $authenticated = false;
        $user = false;

        if(array_key_exists('auth', $factory_config)){

            if($factory_config['auth'] === false){

                $authenticated = true;
            }else{

                list($auth_primary_model, $auth_model_path) = $this->loadModel($factory_config['auth']);

                if(array_key_exists('method', $factory_config['auth']) && method_exists($auth_primary_model, $factory_config['auth']['method'])){

                    if(array_key_exists('param', $factory_config['auth']) && isset($_POST[$factory_config['auth']['param']])){

                        $auth_param = $this->input->post($factory_config['auth']['param'], TRUE);

                        $auth = $this->{$auth_primary_model}->{$factory_config['auth']['method']}($auth_param);

                        if($auth != false){

                            $authenticated = true;

                            /*
                            Query filters
                            */
                            if(array_key_exists('filters', $factory_config['auth'])){

                                $auth_filters = $factory_config['auth']['filters'];

                                foreach($auth_filters as $filter){

                                    $this->db->where($filter['field'], $auth->{$filter['key']});
                                }
                            }
                        }
                    }
                }
            }
        }else{

            if(Auth::guard('sanctum')->check()){
        
                $user = Auth::guard('sanctum')->user();

                if($user){

                    $authenticated = true;
                }
            }
        }

        return [$authenticated, $user];
    }
}
