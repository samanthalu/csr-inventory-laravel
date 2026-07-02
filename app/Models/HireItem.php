<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HireItem extends Model
{
    protected $table = 'hire_items';
    protected $primaryKey = 'id';

    protected $fillable = [
        'hire_id',
        'product_id',
        'quantity',
        'hire_rate_per_day',
        'is_returned',
        'returned_at',
    ];

    protected $casts = [
        'is_returned' => 'boolean',
        'returned_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'prod_id');
    }

    public function hire()
    {
        return $this->belongsTo(Hire::class, 'hire_id', 'id');
    }

    public function invoiceItem()
    {
        return $this->hasOne(InvoiceItem::class, 'hire_item_id');
    }
}
