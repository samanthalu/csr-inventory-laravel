<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;
    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $timestamps = true;

    protected $fillable = [
        'staff_first_name',
        'staff_last_name',
        'staff_email',
        'staff_phone',
        'staff_position',
        'staff_status',
    ];
    
}
