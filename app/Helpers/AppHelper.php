<?php
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;

use App\Models\Settings\SettingsModel;

if(!function_exists("clean_display")){

	function clean_display($str = ''){

		return html_entity_decode(mb_convert_encoding(stripslashes($str), 'HTML-ENTITIES', 'UTF-8'));
		// return stripslashes(utf8_decode($str));
	}
}

if(!function_exists('random_string_generator')){

	function random_string_generator($length = 10, $type = ''){
		
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		if($type == 'numeric'){

			$characters = '012345678901234567890123456789012345678901234567890123456789';
		}

		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}

if(!function_exists('time_elapsed_string')){	//shows time in ago format

	function time_elapsed_string($datetime, $full = false){
		$now = new DateTime;
		$ago = new DateTime($datetime);
		$diff = $now->diff($ago);
	
		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;
	
		$string = array(
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		);
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
				unset($string[$k]);
			}
		}
	
		if (!$full) $string = array_slice($string, 0, 1);
		return $string ? implode(', ', $string) . ' ago' : 'just now';
	}
}

if(!function_exists('currency_format')){
	function currency_format($amount = 0){

		$number_of_digits = strlen($amount);

		$ext = "";
		
		if($number_of_digits > 3){
			if($number_of_digits%2 != 0){
				$divider = currency_divider($number_of_digits - 1);
			}else{
				$divider = currency_divider($number_of_digits);
			}	
		}else{
			$divider = 1;
		}

		$fraction = $amount / $divider;
		$fraction = number_format($fraction, 2);
		
		if($number_of_digits == 4 || $number_of_digits == 5)
			$ext = "k";

		if($number_of_digits == 6 || $number_of_digits == 7)
			$ext = "Lac";

		if($number_of_digits == 8 || $number_of_digits == 9)
			$ext = "Cr";
		return ($fraction + 0) . " " . $ext;
	}
}

if(!function_exists('currency_divider')){

	function currency_divider($number_of_digits=0){

		$tens = "1";
		
		if($number_of_digits > 8)
			return 10000000;
		
		while(($number_of_digits - 1) > 0){
			$tens .= "0";
			$number_of_digits--;
		}
		return $tens;
	}
}



/**
 * Encode an Intervention Image to approximately a target size in bytes.
 *
 * @param \Intervention\Image\Image $image
 * @param string $format e.g. 'jpg'
 * @param int $targetSize Target size in bytes
 * @param int $tolerance How close you need to be (bytes)
 * @return string Encoded binary data
 */
if(!function_exists('encodeToTargetSize')){

	function encodeToTargetSize($image, $format, $targetSize = 300000, $tolerance = 5000){

		$minQuality = 10;
		$maxQuality = 90;
		$bestQuality = $minQuality;
		$bestData = null;

		while ($minQuality <= $maxQuality) {
			$midQuality = intval(($minQuality + $maxQuality) / 2);

			// Create encoder for the format
			$encoder = match ($format) {
				'jpg', 'jpeg' => new JpegEncoder($midQuality),
				'png' => new PngEncoder(), // PNG doesn't use quality scale like JPEG
				'webp' => new WebpEncoder($midQuality),
				default => throw new \Exception("Unsupported format: $format")
			};

			// Encode
			$data = (string)$image->encode($encoder);
			$size = strlen($data);

			if (abs($size - $targetSize) <= $tolerance) {
				// Close enough
				return $data;
			}

			if ($size > $targetSize) {
				// Too big, reduce quality
				$maxQuality = $midQuality - 1;
			} else {
				// Too small, increase quality
				$minQuality = $midQuality + 1;
				$bestQuality = $midQuality;
				$bestData = $data;
			}

			// PNG doesn't benefit from quality adjustments, exit loop
			if ($format === 'png') {
				break;
			}
		}

		return $bestData ?? (string)$image->encode($encoder);
	}
}


if(!function_exists('sitename')){
	function sitename(){
		return "Dollar Traq";
	}
}


if(!function_exists('application_url')){
	function application_url(){
		return "https://app.dollartraq.com/";
	}
}

if(!function_exists('website_url')){
	function website_url(){
		return "https://www.dollartraq.com/";
	}
}

if(!function_exists('get_setting')){
	function get_setting($key=false){
		$setting_methods     = new SettingsModel();
		return $setting_methods->get_value_by_key($key);
	}
}



if(!function_exists('email')){
	function email($from, $from_name, $to, $sub, $body, $html=false, $debug=false, $cc=false){
		
		$html = $body;
		$subject = $sub;
		$email = $to;
		
		 try {
			Mail::send([], [], function ($message) use ($email, $subject, $html) {
				$message->from($from, $from_name)->to($email)->subject($subject)->html($html);
			});

			//$status = true;
			//$message = "Email sent successfully.";

		} catch (\Exception $e) {
			$status = false;
			$message = "Email sending failed: " . $e->getMessage();
		}
	}
}


function send_expo_push($to, $title, $body, $data = []){
	
    $url = 'https://exp.host/--/api/v2/push/send';

    $payload = [
        'to'    => $to,
        'title' => $title,
        'body'  => $body,
        'data'  => $data,
		'priority' => 'high',
		'sound' => 'default',
		'channelId' => 'default'
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_HTTPHEADER      => [
            'Accept: application/json',
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS      => json_encode($payload),
        CURLOPT_TIMEOUT         => 10,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error'   => $error,
        ];
    }

    return json_decode($response, true);
}