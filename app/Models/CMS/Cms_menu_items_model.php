<?php
class Cms_menu_items_model extends WD_Model {

	const STATUS_ACTIVE = 1;
	const STATUS_HIDDEN = 0;
	
	function __construct(){
		$this->set_table_name('cms_menu_items');
		$this->set_table_index('row_id');
	}
}
?>