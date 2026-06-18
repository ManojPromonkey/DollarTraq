<?php
 
namespace App\Models\Customers;
 
use App\Core\CoreModel;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

use Illuminate\Auth\Authenticatable;

use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Auth;
 use Illuminate\Support\Facades\DB;
class CustomersCarriersShortlistedModel extends CoreModel

{

    use Authenticatable, HasApiTokens, Notifiable;
 
    protected $table = 'customers_carriers_shortlisted';

    protected $primaryKey = 'row_id';
 
    public $timestamps = false;
 
    protected $appends = ['risk_score', 'risk_level', 'address'];
 
    public function __construct(){
        $this->setTableIndex('row_id');
    }
 
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */
 
    public function carrier(){
        return $this->belongsTo(\App\Models\carriers\Carrier::class, 'carrier_id', 'row_id');
    }
 
    // public function inspection(){
    //     return $this->hasOne(\App\Models\carriers\Inspection::class, 'dot_number', 'dot_number');
    // }
 
    /*

    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
 
    public function getRiskScoreAttribute(){

        $carrier = $this->carrier;
        if (!$carrier) {
            return 0;
        }
 
        $score =

            (($carrier->driver_total ?? 0) * 2) +

            ($carrier->nbr_power_unit ?? 0) +

            (($carrier->mcs150_mileage ?? 0) / 10000);
 
        return round($score, 2);

    }
 
    public function getRiskLevelAttribute()

    {

        $score = $this->risk_score;
 
        if ($score >= 150) {

            return 'HIGH';

        }
 
        if ($score >= 75) {

            return 'MEDIUM';

        }
 
        return 'LOW';

    }
 
    public function getAddressAttribute()

    {

        $carrier = $this->carrier;
 
        if (!$carrier) {

            return '';

        }
 
        return collect([

            $carrier->phy_street,

            $carrier->phy_city,

            $carrier->phy_state,

            $carrier->phy_zip

        ])->filter()->implode(', ');

    }
 
    /*

    |--------------------------------------------------------------------------

    | FORMAT

    |--------------------------------------------------------------------------

    */
 
    public function format($row = false){
        return $row;
    }
 
    /*

    |--------------------------------------------------------------------------

    | BEFORE SAVE

    |--------------------------------------------------------------------------

    */
 
    public function carrier_shortlisted_before($post, $action, $fields, $user, $account_token, $_input_row_id = false) {
 
        $check_shortlisted = self::where('customer_id', trim($user['row_id']))->where('carrier_id', trim($post['carrier_id']));
 
        if ($check_shortlisted->count() > 0 && $_input_row_id == '') {
            $shortlisted = $check_shortlisted->first();

            die(json_encode([

                'status' => false,

                'message' => 'This carrier already shortlisted. Please use different carrier.'

            ]));

        } else {
 
            return [

                'customer_id' => $user['row_id']

            ];

        }

    }
 
    /*

    |--------------------------------------------------------------------------

    | SHORTLISTED LIST

    |--------------------------------------------------------------------------

    */

    public function shortlisted_list($request, $user){
        //DB::enableQueryLog();
        $perPage = $request->per_page ?? 10;

        $page = $request->page ?? 1;

        $query = self::with([
                'carrier',
                'carrier.inspection'
            ])->select("*", "row_id as rowid")
            ->where('customer_id', $user['row_id'])
            ->orderBy('id', 'desc');

        if (is_array($filters)) {

            foreach ($filters as $filter_key => $filter_value) {

                if ($filter_value === '**' || $filter_value === '') {
                    continue;
                }

                if ($filter_key === 'status') {
                    $listing->where('status', $filter_value);
                } else {
                    $query->where($filter_key, $filter_value);
                }
            }
        }    



        $paginator = $query->paginate(
            $perPage,
            ['*'],
            'page',
            $page
        );
       

        $items = collect($paginator->items())->map(function ($item) {
        return [

           // 'item' => $item,
            'row_id' => $item->rowid,

            'carrier_id' => $item->carrier_id,

            'customer_id' => $item->customer_id,

            'added_on' => $item->added_on,

            /*
            Carrier Details
            */
            'carrier_operation' => optional($item->carrier)->carrier_operation,

            'company_name' => optional($item->carrier)->legal_name,

            'dba_name' => optional($item->carrier)->dba_name,

            'dot_number' => optional($item->carrier)->dot_number,

            'phone' => optional($item->carrier)->telephone,

            'email' => optional($item->carrier)->email_address,

            /*
            Fleet Details
            */
            'mileage' => optional($item->carrier)->mcs150_mileage,

            'fleet_size' => optional($item->carrier)->nbr_power_unit,

            'drivers' => optional($item->carrier)->driver_total,

            /*
            Inspection
            */
            //'vin' => optional($item->inspection)->vin,
            'vin' => optional(optional($item->carrier)->inspection)->vin,
            /*
            Custom Accessors
            */
            'address' => $item->address,

            'risk_score' => $item->risk_score,

            'risk_level' => $item->risk_level,
        ];
    });

    return [

        'status' => true,

        'records' => $items,

        'pagination' => [

            'total' => $paginator->total(),

            'per_page' => $paginator->perPage(),

            'page' => $paginator->currentPage(),

            'last_page' => $paginator->lastPage(),

            'from' => $paginator->firstItem(),

            'to' => $paginator->lastItem(),
        ]
    ];
}

}
 