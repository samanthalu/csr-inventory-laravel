<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $table = 'suppliers';
    protected $primaryKey = 'sup_id';
    public $timestamps = true;

    protected $fillable = [
        'sup_name',
        'sup_address',
        'sup_phone',
        'sup_email',
        'sup_district',
        'sup_type',
        'sup_tax_id',
        'sup_contact_person',
        'sup_contact_phone',
        'sup_bank_details',
        'sup_registration_number',
       
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'sup_id');
    }
}
