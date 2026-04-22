<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HireItem extends Model
{
    protected $table = 'hire_items';
    protected $primaryKey = 'id';

    protected $fillable = [
        'hire_id',
        'product_id',
        'quantity',
        'hire_rate_per_day',
        'is_returned',
    ];

    protected $casts = [
        'is_returned' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'prod_id');
    }
}
