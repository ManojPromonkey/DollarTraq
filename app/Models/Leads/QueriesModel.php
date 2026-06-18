<?php

namespace App\Models\Leads;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\CMS\CMSEmailModel;
use App\Models\Customers\CustomersModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class QueriesModel extends CoreModel
{
    const STATUS_NEW = '0';
	const STATUS_PROCESSED = '1';
	const STATUS_CONVERTED = '2';

	const PAGE_SOURCE_CONTACT = 'contact';
	const PAGE_SOURCE_DEMO = 'demo';    

    protected $table = 'queries';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    public function format($customer = false){

		if($customer){

			$customer->added_on_formatted 	= Carbon::parse($customer->added_on)->format('d M, Y');

			$customer->first_name = clean_display($customer->first_name);
			$customer->last_name = clean_display($customer->last_name);

			$customer->email = clean_display($customer->email);
			$customer->job_title = clean_display($customer->job_title);
			$customer->company = clean_display($customer->company);
			$customer->interest = clean_display($customer->interest);

			$customer->message = clean_display($customer->message);
		}

		return $customer;
	}

	public function after_contact_query($request, $post_data = [], $row_id = false, $action = 'save', $user = false){

		if(array_key_exists('email', $post_data)){

			$first_name = $post_data['first_name'];
			$last_name = $post_data['last_name'];
			$email = $post_data['email'];
		
			/* Email */

			$vars = [];
			$vars['customer_name'] = ucwords(clean_display($first_name) . ' ' . clean_display($last_name));
			$vars['sitename'] = sitename();
			$vars['customer_email'] = clean_display($email);

			$cms_email_model = new CMSEmailModel();

			if(array_key_exists('query_page', $post_data) && $post_data['query_page'] == 'demo'){
				$cms_email_model->send_template_email('demo_query_customer_email', $vars, $email, ucwords(clean_display($first_name) . ' ' . clean_display($last_name)));
			}else{
				$cms_email_model->send_template_email('contact_query_customer_email', $vars, $email, ucwords(clean_display($first_name) . ' ' . clean_display($last_name)));
			}

			/* Admin email*/
			$admin_email = get_setting('store_communication_default_admin_email');

			$contact_form_data = [];

			$n = 1;

			foreach($post_data as $key => $value){

				$bg = ($n % 2 === 0) ? 'rgba(0,0,0,.05)' : 'rgba(0,0,0,.1)';

				if($key == 'contact'){

					if(array_key_exists('c_code', $post_data)){

						$value = "+" . $post_data['c_code'] . " " . $value;
					}
				}

				if($key !== 'c_code' && $key != 'row_id'){

					$contact_form_data[] = "<tr><td width='200' valign='top' style='background-color:{$bg}'>" . ucwords(str_replace("_", " ", $key)) . "</td><td>" . clean_display($value) . "</td></tr>";
				}

				$n++;
			}

			$page = 'Contact';

			if(array_key_exists('query_page', $post_data)){
				$page = ucwords($post_data['query_page']);
			}

			$vars = [];
			$vars['customer_name'] = ucwords(clean_display($first_name) . ' ' . clean_display($last_name));
			$vars['sitename'] = sitename();
			$vars['customer_email'] = clean_display($email);
			$vars['page'] = clean_display($page);
			$vars['contact_form_data'] = "<table cellpadding='8' cellspacing='0' style='font-size:12px;'>" . implode("", $contact_form_data) . "</table>";

			$cms_email_model->send_template_email('contact_query_admin_email', $vars, $admin_email, "Admin");
		}
	}

	public function after_query_update($request, $post_data = [], $row_id = false, $action = 'save', $user = false){

		if($row_id){
			$row = $this->fetch_row_by_id($row_id);
			if($row){
				//die(json_encode(['status' => true, 'row' => $this->format($row)]));
				return ['status' => true, 'row' => $this->format($row)];
			}
		}

		return [];
	}

	public function country_codes(){

		$data = [];

		$data[] = ['key' => 'Canada', 'value' => 'Canada'];
		$data[] = ['key' => 'India', 'value' => 'India'];
		$data[] = ['key' => 'UK', 'value' => 'UK'];
		$data[] = ['key' => 'USA', 'value' => 'USA'];
		
		return $data;
	}

	public function page_sources(){

		$sources = [];
		$sources[] = ['key' => self::PAGE_SOURCE_CONTACT, 'value' => 'Contact Page'];
		$sources[] = ['key' => self::PAGE_SOURCE_DEMO, 'value' => 'Demo Page'];

		return $sources;
	}

	public function query_sources(){

		$sources = [];
		$sources[] = ['key' => 'Google', 'value' => 'Google'];
		$sources[] = ['key' => 'LinkedIn', 'value' => 'LinkedIn'];
		$sources[] = ['key' => 'Referral', 'value' => 'Referral'];
		$sources[] = ['key' => 'Other', 'value' => 'Other'];

		return $sources;
	}

	public function statuses(){

		$status = [];
		
		$status[] = ['key' => self::STATUS_NEW, 'value' => 'New'];
		$status[] = ['key' => self::STATUS_PROCESSED, 'value' => 'Processing'];
		$status[] = ['key' => self::STATUS_CONVERTED, 'value' => 'Converted'];
		
		return $status;
	}


	public function convert_customer($request, $user){
		$customers_model = new CustomersModel();
		
		$row_id = $request->row_id;

		$query = $this->fetch_row_by_id($row_id);

		if($query){
			/* Check if customer exists */
			$customer_email = CustomersModel::where('email', $query->email)->first();

			if($customer_email){
				return ['status' => false, 'message' => "Customer with email address '".$query->email."' already exists."];
			}else{

				$password = random_string_generator(6, 'numeric');

				$_customer_row_id = $customers_model->generate_unique_id("customers_model");

				$customers_model->set_post_data('row_id', $_customer_row_id);
				$customers_model->set_post_data('first_name', $query->first_name);
				$customers_model->set_post_data('last_name', $query->last_name);
				$customers_model->set_post_data('email', $query->email);
				$customers_model->set_post_data('c_code', $query->c_code);
				$customers_model->set_post_data('contact', $query->contact);
				$customers_model->set_post_data('password', Hash::make($password));

				$customers_model->set_post_data('added_on', date('Y-m-d H:i:s'));
				$customers_model->set_post_data('status', $customers_model::STATUS_ENABLED);

				$customers_model->set_post_data('lead_id', $query->row_id);

				$customers_model->post_save();

				/*
				Update lead status
				*/
				$this->set_post_data('updated_on', date('Y-m-d H:i:s'));
				$this->set_post_data('status', self::STATUS_CONVERTED);
				$this->post_update($query->row_id);

				/*
				Email
				*/
				$vars = [];
				$vars['customer_name'] = ucwords(clean_display($query->first_name) . ' ' . clean_display($query->last_name));
				$vars['sitename'] = sitename();
				$vars['application_url'] = application_url();
				$vars['customer_email'] = clean_display($query->email);
				$vars['password'] = clean_display($password);

				$cms_email_model = new CMSEmailModel();

				$cms_email_model->send_template_email('query_to_customer_email', $vars, $query->email, ucwords(clean_display($query->first_name) . ' ' . clean_display($query->last_name)));

				try {

					DB::commit();
					$query = $this->fetch_row_by_id($row_id);
					$query = $this->format($query);

					return ['status'  => true, 'row'     => $query, 'message' => 'Query has been converted into customer.' ];

				} catch (\Exception $e) {

					DB::rollBack();

					return [ 'status'  => false, 'message' => 'There was an error while processing your request.' ];
				}
			}
		}else{
			return ['status' => false, 'message' => 'There was an error while processing your request.'];
		}
	}
    
}
