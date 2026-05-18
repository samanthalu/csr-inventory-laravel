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
        Schema::create('hire_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hire_request_id')->constrained('hire_requests')->onDelete('cascade');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('category_id')->references('cat_id')->on('category')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hire_request_items');
    }
};
