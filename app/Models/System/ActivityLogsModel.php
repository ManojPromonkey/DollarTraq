<?php
namespace App\Models\System;

use App\Modules\Base\Models\BaseModel;

class ActivityLogsModel extends BaseModel
{

    protected $table = 'activity_logs';

    function __construct(){
        $this->setTableIndex('row_id');
		$this->setTableName('activity_logs');
	}

    public function addActivity($act_by_user, $act_by_user_type, $act_code, $act_module, $act_data = []){

        $this->set_post_data('act_by_user', $act_by_user);
        $this->set_post_data('act_by_user_type', $act_by_user_type);
        $this->set_post_data('act_code', $act_code);
        $this->set_post_data('act_module', $act_module);
        $this->set_post_data('created_at', date('Y-m-d H:i:s'));

        if(is_array($act_data) && count($act_data) > 0){

            $this->set_post_data('act_data', json_encode($act_data));
        }

        return $this->post_save();
    }
}
