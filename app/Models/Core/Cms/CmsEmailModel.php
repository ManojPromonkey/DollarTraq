<?php
namespace App\Models\Core\Cms;

use App\Core\CoreModel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CmsEmailModel extends CoreModel
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

		  $row->added_on_formatted 	= Carbon::parse($row->added_on)->format('d M, Y');
        }

        return $row;
    }

    public function before_template_save($post, $action, $fields, $user, $account_token, $_row_id){

        $return = [];

        if($action == 'save'){
		  $return['code'] = Str::slug($post['code']);
        }

        return $return;
    }

	public function process_email($to = '', $template_code = '', $vars = [], $subject = '', $body = ''){

		if($to != ''){

			if($template_code != ''){

				$template = $this->fetch_row_by_field('code', trim($template_code));

				if($template){

					$body = clean_display($template->content);
					$subject = clean_display($template->subject);

					return $this->send_email($to, $subject, $body, $vars);
				}else{

					return ['status' => false, 'message' => 'Template not found.'];
				}
			}else{

				if($body != '' && $subject != ''){

					return $this->send_email($to, $subject, $body, $vars);
				}else{

					return ['status' => false, 'message' => 'Template and mail body not defined.'];
				}
			}
		}else{

			return ['status' => false, 'message' => 'Email receiver is not defined.'];
		}
	}

    public function send_email(
        $to = '',
        $subject = '',
        $body = '',
        $vars = [],
		$cc = [],
        $attachments = [],
        $template = '',
		$plain = false
    ) {

		$body = $this->parse_template($body ?? '', $vars);
		$subject = $this->parse_template($subject ?? '', $vars);

		Mail::send([], [], function ($message) use ($to, $cc, $template, $subject, $body, $attachments, $plain){

			try{
            
				$message->to($to)->subject($subject);
			
				if(!empty($cc)){

					$message->cc($cc);
				}

				/*
				Template based mail
				*/

				if(!$plain){
				
					if(is_string($template) && !empty($template) && view()->exists($template)){

						$body = view($template, [
							'subject' => $subject,
							'body'    => $body
						])->render();
					}

					$body = view('emails.common.email_master', [
						'subject' => $subject,
						'body'    => $body
					])->render();
				
					$message->html($body);

				/*
				Raw email
				*/
				}else if(is_string($body)){
				
					$return = $message->html($body);

					print_r($return);
				}else{
				
					return ['status' => false, 'message' => "Error: template or html not provided."];
				}

				// Attachments
				foreach($attachments as $file){

					$message->attach($file);
				}
			}catch(\Exception $e){

				return ['status' => false, 'message' => $e->getMessage()];
			}
        });

        return ['status' => true];
    }

	public function parse_template($content = false, $variables = array()){

		if($content){
		
			preg_match_all('/{{(.*?)}}/', stripslashes($content), $shortcodes);
		
			$shortcodes = $shortcodes[1];
			
			if(count($shortcodes) > 0){

				foreach($shortcodes as $shortcode){
				
					if(array_key_exists($shortcode, $variables)){
				
						$content = str_replace("{{".$shortcode."}}", $variables[$shortcode], $content);
					}
				}
			}

			return $content;
		}

		return false;
	}
}