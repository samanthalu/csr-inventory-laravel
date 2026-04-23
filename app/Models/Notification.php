<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table      = 'notifs';
    protected $primaryKey = 'notif_id';

    protected $fillable = [
        'notif',
        'notif_by',
        'notif_to',
        'notif_date',
        'notif_status',
    ];

    protected $casts = [
        'notif_date' => 'date',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'notif_by', 'id');
    }
}
