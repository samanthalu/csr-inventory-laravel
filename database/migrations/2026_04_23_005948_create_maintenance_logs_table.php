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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('prod_id')->on('products')->cascadeOnDelete();
            $table->date('ml_sent_date');
            $table->date('ml_expected_return_date')->nullable();
            $table->date('ml_actual_return_date')->nullable();
            $table->string('ml_technician')->nullable();
            $table->decimal('ml_cost', 10, 2)->nullable();
            $table->text('ml_reason');
            $table->text('ml_notes')->nullable();
            $table->enum('ml_status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
