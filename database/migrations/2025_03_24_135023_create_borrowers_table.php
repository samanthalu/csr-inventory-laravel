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
        Schema::create('borrowers', function (Blueprint $table) {
            $table->id('pb_id');
            $table->string('pb_name');
            $table->string('pb_purpose');
            $table->date('pb_date_from');
            $table->date('pb_date_to');
            $table->string('pb_with_accessories', 10)->default('no');
            $table->unsignedInteger('pb_prod_id');
            $table->foreign('pb_prod_id')->references('prod_id')->on('product')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrowers');
    }
};
