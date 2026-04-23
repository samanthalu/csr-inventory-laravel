<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisposalRecord extends Model
{
    protected $table = 'disposal_records';

    protected $fillable = [
        'product_id',
        'dr_disposal_date',
        'dr_method',
        'dr_reason',
        'dr_authorised_by',
        'dr_value_at_disposal',
        'dr_recipient',
        'dr_notes',
        'dr_status',
    ];

    protected $casts = [
        'dr_disposal_date'     => 'date',
        'dr_value_at_disposal' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'prod_id');
    }
}
