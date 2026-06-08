<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaAssetAssignment extends Model
{
    protected $table = 'ra_asset_assignments';

    protected $fillable = [
        'raa_session_id', 'raa_ra_id', 'raa_product_id',
        'raa_date_out', 'raa_expected_return',
        'raa_date_returned', 'raa_condition_out',
        'raa_condition_in', 'raa_notes',
    ];

    protected $casts = [
        'raa_date_out'        => 'date:Y-m-d',
        'raa_expected_return' => 'date:Y-m-d',
        'raa_date_returned'   => 'date:Y-m-d',
    ];

    public function assistant()
    {
        return $this->belongsTo(ResearchAssistant::class, 'raa_ra_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'raa_product_id', 'prod_id');
    }

    public function session()
    {
        return $this->belongsTo(FieldWorkSession::class, 'raa_session_id');
    }
}
