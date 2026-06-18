<?php

namespace App\Models\carriers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierAuthorityHistory extends Model
{
    protected $table = 'carrier_authority_history';

    protected $fillable = ['docket_number', 'dot_number', 'row_id', 'sub_number', 'op_auth_type', 'original_action_desc', 'orig_served_date', 'disp_action_desc', 'disp_decided_date', 'disp_served_date'];

    protected $casts = ['disp_decided_date' => 'date', 'disp_served_date' => 'date'];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'dot_number', 'dot_number');
    }
}
