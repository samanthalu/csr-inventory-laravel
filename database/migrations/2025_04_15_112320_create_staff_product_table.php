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
        Schema::create('staff_product', function (Blueprint $table) {
            $table->id('sp_id');
            $table->unsignedBigInteger('sp_pb_id');
            $table->unsignedBigInteger('sp_staff_id');
            $table->unsignedInteger('sp_prod_id');
            // $table->timestamp('assigned_at')->useCurrent();
            $table->foreign('sp_staff_id')->references('staff_id')->on('staff')->onDelete('restrict');
            $table->foreign('sp_pb_id')->references('pb_id')->on('borrowers')->onDelete('restrict');
            $table->foreign('sp_prod_id')->references('prod_id')->on('products')->onDelete('restrict');
            $table->unique(['sp_staff_id', 'sp_prod_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_product');
    }
};
