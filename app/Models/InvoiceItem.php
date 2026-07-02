<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'hire_item_id',
        'days',
        'rate_per_day',
        'subtotal',
    ];

    protected $casts = [
        'rate_per_day' => 'decimal:2',
        'subtotal'     => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function hireItem()
    {
        return $this->belongsTo(HireItem::class, 'hire_item_id');
    }
}
