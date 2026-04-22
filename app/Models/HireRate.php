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
    ];
}
