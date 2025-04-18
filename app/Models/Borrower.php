<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    use HasFactory;

    protected $table = 'borrowers';
    protected $primaryKey = 'pb_id';
    public $timestamps = true;

    protected $fillable = [
        'pb_name',
        'pb_purpose',
        'pb_date_from',
        'pb_date_to',
        'pb_status', // Include any other fields you want to be mass-assignable
        'pb_with_accessories',
        'staff_id', // Foreign key to staff table 
    ];


    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }


    public function borrowedDevices()
    {
        return $this->hasMany(StaffProduct::class, 'sp_pb_id', 'pb_id');
    }

}
