<?php

namespace App\Models\Misc;

use App\Core\CoreModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\Misc\CountriesStates;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Countries extends CoreModel
{
    protected $table = 'countries';

    function __construct(){
		
        $this->setTableIndex('id');
	}
	
	public function countries_list(){

		$countries = [];

		$results = self::where('name', '!=', '')->orderBy('name', 'asc')->get();

		foreach($results as $result){
			$countries[] = ['key' => $result->iso2_code, 'value' => clean_display($result->name), 'dial_code' => clean_display($result->dial_code)];
		}

		return $countries;
	}

	public function country_states($countries_limit = []){
		$countries = [];

		$query = self::query()->where('iso2_code', 'IN')->where('name', '!=', '')->orderBy('sort_order', 'desc')->orderBy('name', 'asc');
		// whereIn
		if (is_array($countries_limit) && count($countries_limit) > 0) {
			$query->whereIn('country_id', $countries_limit);
		}

		$results = $query->get();

		// states
		$states = CountriesStates::query()->get();

		$_states = [];

		if ($states->count() > 0) {

			foreach ($states as $state) {

				$_states[$state->country][] = [
					'country' => clean_display($state->country),
					'value'   => clean_display($state->name),
					'key'     => clean_display($state->code),
				];
			}
		}

		if ($results->count() > 0) {

			foreach ($results as $country) {

				$row = [
					'key'       => $country->iso2_code,
					'value'     => clean_display($country->name),
					'dial_code' => clean_display($country->dial_code),
					'states'    => [],
				];

				if (array_key_exists($country->iso2_code, $_states)) {

					$row['states'] = $_states[$country->iso2_code];
				}

				$countries[] = $row;
			}
		}

		return $countries;
	}



	public function country_codes(){

		//$countries = file_get_contents('./assets/country-codes.json');
		$countries = file_get_contents(public_path('assets/country-codes.json'));

		$list = [];

		$countries = json_decode($countries, true);

		foreach($countries as $country){

			$flag_url = asset("/assets/flags/".strtolower(str_replace([" ", "-"], "_", str_replace(["(", ")", ",", "."], "", $country['country']))).".png");

			$country_name = "+" . $country['calling_code'] . " " . ucwords(str_replace([" ", "-"], " ", str_replace(["(", ")", "."], "", $country['country'])));

			$list[] = ['key' => $country['calling_code'], 'value' => $country_name, 'flag' => $flag_url];
		}

		return $list;
	}

	public function timezones(){
		$timezones = file_get_contents(public_path('./assets/timezones.json'));
		$list = [];
		$timezones = json_decode($timezones, true);
		foreach($timezones as $timezone){
			$list[] = ['key' => $timezone['abbr'], 'value' => $timezone["value"], 'text' => $timezone["text"]];
		}
		return $list;
	}
}
?>