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
        'pb_prod_id', // Include any other fields you want to be mass-assignable
        'pb_with_accessories'
    ];

    public function products() {
        return $this->hasMany(Product::class, 'prod_id');
    }
}
