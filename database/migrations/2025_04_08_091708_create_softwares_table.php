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
        Schema::create('softwares', function (Blueprint $table) {
            $table->id('soft_id');
            $table->string('soft_name');
            $table->string('soft_version');
            $table->string('soft_category');
            $table->string('soft_desc');
            $table->unsignedInteger('sup_id');
            $table->foreign('sup_id')->references('sup_id')->on('suppliers')->onDelete('restrict');
            $table->date('soft_date_purchased')->nullable();
            $table->string('soft_license_type');
            $table->date('soft_license_from')->nullable();
            $table->date('soft_license_to')->nullable();
            $table->text('soft_license')->nullable();
       
            // licence type,
            // licence duration
            // licence key,

        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('softwares');
    }
};
