<?php

namespace App\Models\Shipments;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

use App\Models\Shipments\ShipmentsModel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

///
class ShipmentsDocuments extends CoreModel
{
    protected $table = 'shipment_docs';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($row = false){

		if($row){
			$row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');

			$row->document_url = '';
			if($row->document != ''){
				$row->document_url = URL::to(Storage::url('uploads/pod/' . clean_display($row->document)));
			}
		}

		return $row;
	}

	public function load_documents($shipment_id){
		$documents = self::where('shipment_id', $shipment_id);
		$_documents = [];
		if($documents->count() > 0){

			$documents = $documents->get();
			foreach($documents as $document){
				if($document->document != ''){
					$_documents[] = $this->format($document);
				}
			}
		}

		return $_documents;
	}


	public function tracking_document_remove($request, $user){

		$shipment_row_id  	= $request->filled('row_id') ? $request->post('row_id') : '';
		$document		  	= $request->filled('document') ? $request->post('document') : '';
		
		$shipments_model		= new ShipmentsModel();

		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);

		if($shipment){
			$document_row = $this->fetch_row_by_id($document);
			if($document_row){
				$file_path = public_path('media/uploads/pod/' . $document_row->document);

				if(file_exists($file_path)) {
					unlink($file_path);
				}

				self::where('row_id', $document)->delete();
			}

			$documents = $this->load_documents($shipment->row_id);

			return ['status' => true, 'documents' => $documents, 'message' => 'Document has been removed successfully.'];
		}else{
			return ['status'=>false, 'message' => 'Information missing.'];
		}

	}


	public function tracking_document_upload($request, $user){

		$shipment_row_id  	= $request->filled('row_id') ? $request->post('row_id') : '';
		$document		  	= $request->filled('document') ? $request->post('document') : '';
		
		$shipments_model		= new ShipmentsModel();

		$shipment = $shipments_model->fetch_row_by_id($shipment_row_id);

		if($shipment){

			$row_id = $this->generate_unique_id();

			$this->set_post_data('row_id', $row_id);
			$this->set_post_data('shipment_id', $shipment->row_id);
			$this->set_post_data('driver_id', $user['row_id']);
			$this->set_post_data('document', $document);
			$this->set_post_data('added_on', date('Y-m-d H:i:s'));
			$this->set_post_data('status', 1);

			$this->post_save();

			/* Load all docs */
			$documents = $this->load_documents($shipment->row_id);

			return ['status' => true, 'documents' => $documents, 'message' => 'Document has been uploaded successfully.'];
		}else{
			return ['status'=>false, 'message' => 'Information missing.'];
		}

	}
    
    
}
