<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductAccessories;
use App\Models\ProductFiles;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'prod_id';

    protected $table = 'product';
    protected $fillable = [
        'prod_name',
        'prod_desc',
        'prod_cost',
        'prod_quantity',
        'prod_serial_num',
        'prod_tag_number',
        'prod_model_number',
        'prod_batch_number',
        'prod_other_identifier',
        'prod_quantity_measure',
        'prod_purchase_date',
        'cat_id',
        'sup_id',
        'order_id',
        'user_id',
        'prod_notes',
        'prod_warranty_expire',
        'prod_condition',
        'prod_current_status'
    ];

    // This enables automatic timestamp handling
    public $timestamps = true;

    protected $casts = [
        'prod_cost' => 'decimal:2',
        'prod_quantity' => 'integer',
        'prod_purchase_date' => 'date',
        'prod_warranty_expire' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // app/Models/Product.php

    public function accessories()
    {
        return $this->hasMany(ProductAccessories::class, 'pa_prod_id'); // pa_prod_id = foreign key
    }

    public function files()
    {
        return $this->hasMany(ProductFiles::class, 'pf_prod_id'); // pf_prod_id = foreign key
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'sup_id', 'sup_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'cat_id', 'cat_id');
    }

    public function borrower() {
        return $this->belongsTo(Borrower::class, 'pb_prod_id', 'prod_id');
    }
}
