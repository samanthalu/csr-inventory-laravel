<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_funding', function (Blueprint $table) {
            $table->increments('pf_id');
            $table->string('pf_source');            
            $table->string('pf_pi')->nullable();
            $table->string('pf_product_type')->nullable();
            $table->string('pf_brand')->nullable();
            $table->integer('pf_quantity');
            $table->date('pf_country_from');
            $table->date('pf_invoice_no')->nullable();
            $table->date('pf_total_cost')->nullable();
            $table->date('pf_supplier');
            $table->date('pf_delivery_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_funding');
    }
};
