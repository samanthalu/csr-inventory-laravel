<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAccessories extends Model
{
    use HasFactory;
    protected $table = 'product_accessories';
    protected $primaryKey = 'pa_id';
    public $timestamps = true;

    protected $fillable = [
        'pa_name',
        'pa_serial_number',
        'pa_qty',
        'pa_color',
        'pa_desc',
        'pa_prod_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'pa_prod_id');
    }


}
