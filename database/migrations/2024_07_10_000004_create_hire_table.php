<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hires');
        Schema::create('hires', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->date('hire_date');
            $table->date('hire_return_date');
            $table->enum('hire_status', ['active', 'returned', 'overdue'])->default('active');
            $table->string('hire_purpose')->nullable();
            $table->text('hire_notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('staff_id')->on('staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hires');
    }
};
