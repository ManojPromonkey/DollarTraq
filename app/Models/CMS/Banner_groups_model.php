<?php
class Banner_groups_model extends WD_Model {
	
	function __construct(){
		$this->set_table_name('cms_banner_groups');
		$this->set_table_index('row_id');
	}
}
?>