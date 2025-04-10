<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $table = 'category';
    protected $primaryKey = 'cat_id';
    public $timestamps = true;
 
    protected $fillable = [
        'cat_name',
        'cat_desc',
        // 'cat_hireable',
        // 'cat_slug',
        // 'cat_status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'cat_id');
    }
}
