<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResearchAssistant extends Model
{
    protected $table = 'research_assistants';

    protected $fillable = [
        'ra_fw_session_id', 'ra_name', 'ra_phone',
        'ra_email', 'ra_id_number', 'ra_district', 'ra_notes',
    ];

    public function session()
    {
        return $this->belongsTo(FieldWorkSession::class, 'ra_fw_session_id');
    }

    public function assignments()
    {
        return $this->hasMany(RaAssetAssignment::class, 'raa_ra_id');
    }
}
