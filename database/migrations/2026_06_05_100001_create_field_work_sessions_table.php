<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_work_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('fw_title');
            $table->text('fw_description')->nullable();
            $table->string('fw_location')->nullable();
            $table->date('fw_start_date');
            $table->date('fw_end_date')->nullable();
            $table->enum('fw_status', ['active', 'completed', 'cancelled'])->default('active');
            $table->unsignedBigInteger('fw_hire_id')->nullable();
            $table->unsignedBigInteger('fw_created_by');
            $table->timestamps();

            $table->foreign('fw_hire_id')->references('id')->on('hires')->nullOnDelete();
            $table->foreign('fw_created_by')->references('id')->on('users');
        });

        Schema::create('research_assistants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ra_fw_session_id');
            $table->string('ra_name');
            $table->string('ra_phone')->nullable();
            $table->string('ra_email')->nullable();
            $table->string('ra_id_number')->nullable();
            $table->string('ra_district')->nullable();
            $table->text('ra_notes')->nullable();
            $table->timestamps();

            $table->foreign('ra_fw_session_id')->references('id')->on('field_work_sessions')->cascadeOnDelete();
        });

        Schema::create('ra_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('raa_session_id');
            $table->unsignedBigInteger('raa_ra_id');
            $table->unsignedInteger('raa_product_id');
            $table->date('raa_date_out');
            $table->date('raa_expected_return')->nullable();
            $table->date('raa_date_returned')->nullable();
            $table->enum('raa_condition_out', ['good', 'fair', 'poor'])->default('good');
            $table->enum('raa_condition_in', ['good', 'fair', 'poor', 'damaged'])->nullable();
            $table->text('raa_notes')->nullable();
            $table->timestamps();

            $table->foreign('raa_session_id')->references('id')->on('field_work_sessions')->cascadeOnDelete();
            $table->foreign('raa_ra_id')->references('id')->on('research_assistants')->cascadeOnDelete();
            $table->foreign('raa_product_id')->references('prod_id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ra_asset_assignments');
        Schema::dropIfExists('research_assistants');
        Schema::dropIfExists('field_work_sessions');
    }
};
