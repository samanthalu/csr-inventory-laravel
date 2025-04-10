<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Software extends Model
{
    use HasFactory;
    protected $table = 'softwares';
    protected $primaryKey = 'soft_id';

    protected $fillable = [
        'soft_name',
        'soft_version',
        'soft_category',
        'soft_desc',
        'sup_id',
        'soft_date_purchased',
        'soft_license_type',
        'soft_license_from',
        'soft_license_to',
        'soft_license',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'sup_id');
    }
}
