<?php

namespace App\Models\carriers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionViolation extends Model
{
    protected $table = 'inspection_violations';

    protected $fillable = [
        'change_date', 'inspection_id_raw', 'row_id', 'inspection_unique_id', 'insp_violation_id',
        'seq_no', 'part_no', 'part_no_section', 'insp_viol_unit', 'insp_unit_id',
        'insp_violation_category_id', 'out_of_service_indicator', 'defect_verification_id', 'citation_number',
    ];

    protected $casts = ['change_date' => 'date', 'out_of_service_indicator' => 'boolean'];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class, 'inspection_unique_id', 'unique_id');
    }
}
