<?php

namespace App\Models\CMS;

use App\Core\CoreModel;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;

use App\Models\Customers\CustomersModel;

use Illuminate\Support\Facades\View;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CMSEmailModel extends CoreModel
{
    	  

    protected $table = 'cms_email_template';

    function __construct(){
        $this->setTableIndex('row_id');
	}

    	public function format($row = false){
        if($row){
            $row->title = clean_display($row->title);
            $row->subject = clean_display($row->subject);
            $row->content = clean_display($row->content);
		  $row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M Y, h:i A');
        }

        return $row;
    	}

	public function before_template_save($post = [], $action, $fields = [], $user = false, $account_token = ''){
        $return = [];
        if($action == 'save'){
			$code = $this->generate_slug($post['code'], '_');
			$return['code'] = $this->validate_slug($code, 'code', $this->getTable(), '_');
        }

        return $return;
    }

    public function send_template_email($template_code = false, $email_vars = [], $to = false, $to_name = false, $from = false, $from_name = false, $html = true, $preview = false, $debug = false){

		$header = view('emails.common.header', $email_vars)->render();
		$footer = view('emails.common.footer', $email_vars)->render();

		$data = [];
		$data['header'] = $header;
		$data['footer'] = $footer;

		if($template_code != false && $template_code != ''){

			$template = $this->get_email_by_code($template_code);

			if($template){
				$subject 	= $this->template_parser(clean_display($template->subject), $email_vars);
				$body 	= $this->template_parser(clean_display($template->content), $email_vars);

				$data['body'] = $body;

				$footer = view('emails.common.body', $data)->render();

				if($preview){
					return ['subject' => $subject, 'body' => $body, 'template' => $template];
				}

				if($to !== false && $to_name != false){

					$email_from = $this->mail_from();

					if($from !== false){
	
						$email_from = $from;
					}
	
					$email_name = $this->mail_name();
	
					if($from_name !== false){
	
						$email_name = $from_name;
					}

					$_cc = false;

					if($template->cc != ''){

						$_cc = $template->cc;
					}
	
					$response = email($email_from, $email_name, $to, $subject, $body, $html, $debug);

					if($_cc !== false){

						email($email_from, $email_name, $_cc, $subject, $body, $html, $debug);
					}
	
					return ['status' => 'success', 'message' => 'Email sent successfully!', 'response' => $response];
				}else{

					return ['status' => 'error', 'message' => 'Template not found!'];
				}
			}else{

				return ['status' => 'error', 'message' => 'Template not found!'];
			}
		}else{

			return ['status' => 'error', 'message' => 'Template code missing!'];
		}
	}

	public function get_email_by_code($code = false){

		if($code && $code != ''){
			$_template = self::where('code', trim($code));
			if($_template->count() > 0){
				return $_template->first();
			}
		}
		return false;
	}


	public function template_parser($content = '', $variables = []){
		if($content != ''){
			preg_match_all('/{{(.*?)}}/', stripslashes($content), $matched_vars);
			
			if(count($matched_vars[0]) > 0){
			
				foreach($matched_vars[0] as $matched_var){
					$var = str_replace(array("{{", "}}"), "", $matched_var);
			
					if(array_key_exists($var, $variables)){
						$content = str_replace($matched_var, $variables[$var], $content);
					}
				}
			}
		}

		return $content;
	}
    
	public function mail_from(){
		return get_setting('store_communication_default_mail');
	}

	public function mail_name(){
		return get_setting('store_communication_default_mail_from');
	}

	public function template_email($email_code=false, $to=false, $to_name=false, $params=array(), $tempate=false, $data = [], $html=true, $debug=false){

		//$this->load->helper('utility_helper');
		
		if(($email_code && $email_code != '') && ($to && $to != '') && ($to_name && $to_name != '')){
			
			//Fetch email template
			$email_template = $this->get_email_by_code($email_code);

			if($email_template){
				$subject = clean_display($email_template->subject);
				
				//Parse subject
				if($subject != ''){
					$subject = $this->template_parser($subject, $params);
				}

				$body_content = clean_display($email_template->content);

				if($body_content != ''){
					$body_content = $this->template_parser($body_content, $params);
				}

				$data['body_content'] = $body_content;

				$email_body = $this->load->view($this->theme->get_view($tempate), $data, true);;

				$_body = $this->load->view($this->theme->get_view('emails/core'), ['body' => $email_body], true);

				return email($this->mail_from(), $this->mail_name(), $to, $subject, $_body, $html, $debug);
			}
		}

		return false;
	}


	public function send_email($email_code=false, $to=false, $to_name=false, $params=array(), $html=true, $debug=false){
		//$this->load->helper('utility_helper');
		
		if(($email_code && $email_code != '') && ($to && $to != '') && ($to_name && $to_name != '')){
			
			//Fetch email template
			$email_template = $this->get_email_by_code($email_code);

			if($email_template){
				
				$subject = clean_display($email_template->subject);
				$body = clean_display($email_template->content);
				
				//Parse subject
				if($subject != ''){
					$subject = $this->template_parser($subject, $params);
				}
				
				$web_logo = asset("/assets/images/logo.png");
				//Parse template
				if($body != ''){
					$body = $this->template_parser($body, $params);
					
					if($html){
						$_body = "<html>";
							$_body .= "<head>";
							$_body .= "</head>";
							
							$_body .= "<body>";
								$_body .= '<table width="700" cellspacing="0" cellpadding="0">';
									$_body .= '<tbody>';
										$_body .= '<tr style="background:#ffffff; height:63px;vertical-align:middle">';
											$_body .= '<td colspan="2" style="padding:10px 5px 10px 20px; text-align:center">';
												$_body .= '<img style="max-height:60px" src="'.$web_logo.'">';
											$_body .= '</td>';
											
										$_body .= '</tr>';
									
										$_body .= '<tr>';
											$_body .= '<td colspan="2" style="height:5px;background-color:#ffffff"></td>';
										$_body .= '</tr>';
										
										$_body .= '<tr>';
											$_body .= '<td colspan="2" style="background:#ffffff;color:#222222;line-height:18px;padding:10px 20px">';
												$_body .= nl2br($body);
											$_body .= '</td>';
										$_body .= '</tr>';

										if(is_array($params) && array_key_exists('before_footer', $params)){

											$_body .= $params['before_footer'];
										}
										
										$_body .= '<tr>';
											$_body .= '<td colspan="2" style="background:#ffffff;padding:10px 20px;color:#666">';
												$_body .= '<table width="100%" cellspacing="0" cellpadding="0">';
													$_body .= '<tbody>';
														$_body .= '<tr>';
															$_body .= '<td style="vertical-align:top;text-align:left;width:50%">';
																$_body .= '<span><a href="'.base_url().'" target="_blank">PaulPeart.com</a> - All rights reserved </span>';
															$_body .= '</td>';
															
															$_body .= '<td style="text-align:right;width:50%">';
																$_body .= '<a href="mailto:'.get_setting('mail_from').'" target="_blank">'.get_setting('mail_from').'</a> <br>';
															$_body .= '</td>';
														$_body .= '</tr>';
													$_body .= '</tbody>';
												$_body .= '</table>';
											$_body .= '</td>';
										$_body .= '</tr>';
									$_body .= '</tbody>';
								$_body .= '</table>';
							$_body .= "</body>";
						$_body .= "</html>";
						// $body = nl2br($body);
					}else{
						$_body = $body;
					}
				}
				
				return email($this->mail_from(), $this->mail_name(), $to, $subject, $_body, $html, $debug);
			}
		}
		return false;
	}
	
}
