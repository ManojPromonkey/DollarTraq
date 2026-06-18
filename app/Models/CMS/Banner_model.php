<?php
class Banner_model extends WD_Model {
	
	function __construct(){
		$this->set_table_name('cms_banner');
		$this->set_table_index('row_id');
	}

	public function format($row = false){

		if($row){

			$row->title = clean_display($row->title);

			$row->banner_image_url = '';

			if($row->banner_image != ''){

				$row->banner_image_url = media_url() . 'uploads/cms/banners/' . clean_display($row->banner_image);
			}
		}

		return $row;
	}
}
?>