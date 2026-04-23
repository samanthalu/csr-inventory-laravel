<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HireRate extends Model
{
    protected $table = 'hire_rates';
    protected $primaryKey = 'hr_id';

    protected $fillable = [
        'hr_item_category',
        'hr_rate',
        'hr_rate_per_week',
        'hr_rate_per_month',
    ];

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'hr_item_category', 'cat_id');
    }
}
