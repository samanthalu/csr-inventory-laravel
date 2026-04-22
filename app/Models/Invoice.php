<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['hire_id', 'invoice_number', 'file_path', 'total_amount'];

    public function hire()
    {
        return $this->belongsTo(Hire::class, 'hire_id');
    }
}
