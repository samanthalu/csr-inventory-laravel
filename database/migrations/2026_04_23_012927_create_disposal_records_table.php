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
        Schema::create('disposal_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('prod_id')->on('products')->cascadeOnDelete();
            $table->date('dr_disposal_date');
            $table->enum('dr_method', ['auction', 'scrap', 'donation', 'written_off', 'stolen', 'other']);
            $table->text('dr_reason');
            $table->string('dr_authorised_by')->nullable();
            $table->decimal('dr_value_at_disposal', 10, 2)->nullable();
            $table->string('dr_recipient')->nullable();
            $table->text('dr_notes')->nullable();
            $table->enum('dr_status', ['pending_approval', 'approved', 'completed'])->default('pending_approval');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disposal_records');
    }
};
