<?php

namespace App\Models\carriers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionCitation extends Model
{
    protected $table = 'inspection_citations';

    protected $fillable = ['change_date', 'row_id', 'inspection_id', 'vioseqnum', 'adjseq', 'citation_code', 'citation_result'];

    protected $casts = ['change_date' => 'date'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class, 'inspection_id', 'unique_id');
    }
}
