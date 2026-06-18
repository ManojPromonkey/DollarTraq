<?php

namespace App\Models\carriers;

use Illuminate\Database\Eloquent\Model;

class BrokerInsurance extends Model
{
    protected $table = 'broker_insurance';

    protected $fillable = [
        'prefix_docket_number', 'ins_type_code', 'ins_class_code', 'row_id',
        'max_cov_amount', 'underl_lim_amount', 'policy_no', 'effective_date', 'ins_form_code', 'name_company',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'max_cov_amount' => 'decimal:2',
        'underl_lim_amount' => 'decimal:2',
    ];
}
