<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HireRequestItem extends Model
{
    protected $fillable = ['hire_request_id', 'category_id', 'quantity'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'cat_id');
    }
}
