<?php

namespace App\Models\carriers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarrierOosOrder extends Model
{
    protected $table = 'carrier_oos_orders';

    protected $fillable = ['dot_number', 'row_id', 'legal_name', 'dba_name', 'oos_date', 'oos_reason', 'status', 'rescind_date'];

    protected $casts = ['oos_date' => 'date', 'rescind_date' => 'date'];

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'dot_number', 'dot_number');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('rescind_date');
    }
}
