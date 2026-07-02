<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['hire_id', 'invoice_number', 'file_path', 'total_amount', 'emailed_at', 'emailed_to'];

    protected $casts = ['emailed_at' => 'datetime'];

    public function hire()
    {
        return $this->belongsTo(Hire::class, 'hire_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }
}
