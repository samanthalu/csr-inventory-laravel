<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffProduct extends Model
{
    use HasFactory;
    protected $table = 'staff_product';
    protected $primaryKey = 'sp_id';
    public $timestamps = true;

    protected $fillable = [
        'sp_prod_id',
        'sp_staff_id',
        'sp_pb_id',
    ];

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'sp_pb_id', 'pb_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'sp_staff_id', 'staff_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'sp_prod_id', 'prod_id');
    }
}
