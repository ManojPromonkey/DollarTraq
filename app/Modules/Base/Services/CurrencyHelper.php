<?php
namespace App\Modules\Base\Services;

class CurrencyHelper {

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