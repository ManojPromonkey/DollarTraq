<?php

namespace App\Models\Misc;

use App\Core\CoreModel;
use Illuminate\Support\Facades\DB;

class CountriesStates extends CoreModel
{
    protected $table = 'countries_states';

    function __construct(){
		
        $this->setTableIndex('id');
	}

	public function indian_states(){
		$states = [];
		$_states = self::where('country', 'IN')->get();
		if($_states->count() > 0){
			foreach($_states as $state){
				$states[] = ['key' => $state->code, 'value' => clean_display($state->name)];
			}
		}

		return $states;
	}

	public function country_states($request, $user){

		$states = [];

		$_states = self::query()->get();

		if(isset($request->country_code)){
			$_states->where('country', $country_code);
		}

		if($_states->count() > 0){

			$_states = $_states->get();

			foreach($_states as $state){

				$states[] = ['key' => $state->id, 'name' => clean_display($state->name), 'code' => clean_display($state->code), 'country' => clean_display($state->country)];
			}
		}

		return $states;
	}
}
?>