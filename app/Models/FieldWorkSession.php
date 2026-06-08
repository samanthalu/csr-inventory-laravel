<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldWorkSession extends Model
{
    protected $table = 'field_work_sessions';

    protected $fillable = [
        'fw_title', 'fw_description', 'fw_location',
        'fw_start_date', 'fw_end_date', 'fw_status',
        'fw_hire_id', 'fw_created_by',
    ];

    protected $casts = [
        'fw_start_date' => 'date:Y-m-d',
        'fw_end_date'   => 'date:Y-m-d',
    ];

    public function hire()
    {
        return $this->belongsTo(Hire::class, 'fw_hire_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'fw_created_by');
    }

    public function assistants()
    {
        return $this->hasMany(ResearchAssistant::class, 'ra_fw_session_id');
    }

    public function assignments()
    {
        return $this->hasMany(RaAssetAssignment::class, 'raa_session_id');
    }
}
