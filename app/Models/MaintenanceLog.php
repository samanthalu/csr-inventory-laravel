<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    protected $table = 'maintenance_logs';

    protected $fillable = [
        'product_id',
        'ml_sent_date',
        'ml_expected_return_date',
        'ml_actual_return_date',
        'ml_technician',
        'ml_cost',
        'ml_reason',
        'ml_notes',
        'ml_status',
    ];

    protected $casts = [
        'ml_sent_date'            => 'date',
        'ml_expected_return_date' => 'date',
        'ml_actual_return_date'   => 'date',
        'ml_cost'                 => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'prod_id');
    }
}
