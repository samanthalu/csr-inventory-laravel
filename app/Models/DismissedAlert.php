<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DismissedAlert extends Model
{
    protected $table    = 'dismissed_alerts';
    public $timestamps  = false;

    protected $fillable = ['user_id', 'alert_key'];

    protected $casts = ['dismissed_at' => 'datetime'];
}
