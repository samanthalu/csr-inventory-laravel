<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_accessories', function (Blueprint $table) {
            $table->string('pa_serial_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_accessories', function (Blueprint $table) {
            $table->string('pa_serial_number')->nullable(false)->change();
        });
    }
};
