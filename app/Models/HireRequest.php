<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HireRequest extends Model
{
    protected $fillable = [
        'staff_id',
        'requested_by',
        'purpose',
        'notes',
        'requested_start_date',
        'requested_end_date',
        'status',
        'reviewed_by',
        'review_note',
    ];

    protected $casts = [
        'requested_start_date' => 'date:Y-m-d',
        'requested_end_date'   => 'date:Y-m-d',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items()
    {
        return $this->hasMany(HireRequestItem::class);
    }
}
