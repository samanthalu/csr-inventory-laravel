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
        Schema::create('hire_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->string('purpose');
            $table->text('notes')->nullable();
            $table->date('requested_start_date');
            $table->date('requested_end_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('staff_id')->on('staff')->onDelete('set null');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hire_requests');
    }
};
