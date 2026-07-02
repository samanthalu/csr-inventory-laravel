<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('hire_item_id')->constrained('hire_items')->cascadeOnDelete();
            $table->unsignedInteger('days');
            $table->decimal('rate_per_day', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            // An item can only be billed once.
            $table->unique('hire_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
