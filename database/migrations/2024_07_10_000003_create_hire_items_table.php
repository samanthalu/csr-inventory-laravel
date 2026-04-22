<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hire_items');
        Schema::create('hire_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hire_id');
            $table->unsignedInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->decimal('hire_rate_per_day', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('hire_id')->references('id')->on('hires')->onDelete('cascade');
            $table->foreign('product_id')->references('prod_id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hire_items');
    }
};
