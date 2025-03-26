<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFiles extends Model
{
    use HasFactory;
    protected $table = 'product_files';
    protected $primaryKey = 'pf_id';
    public $timestamps = true;

    protected $fillable = [
        'pf_file_name',
        'pf_file_size',
        'pf_file_path',
        'pf_file_type',
        'pf_prod_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'pf_prod_id');
    }

}
