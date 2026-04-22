<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hire extends Model
{
    protected $table = 'hires';
    protected $primaryKey = 'id';

    protected $fillable = [
        'staff_id',
        'hire_date',
        'hire_return_date',
        'hire_status',
        'hire_purpose',
        'hire_notes',
    ];

    protected $casts = [
        'hire_date'        => 'date:Y-m-d',
        'hire_return_date' => 'date:Y-m-d',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    public function items()
    {
        return $this->hasMany(HireItem::class, 'hire_id', 'id');
    }
}
