<?php

namespace App\Models\carriers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierContact extends Model
{
    protected $table = 'carrier_contacts';

    protected $fillable = ['docket_number', 'row_id', 'dot_number', 'co_name', 'attn_to_or_title', 'street_po', 'city', 'state_code', 'ctry_code', 'zip_code'];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'dot_number', 'dot_number');
    }
}
