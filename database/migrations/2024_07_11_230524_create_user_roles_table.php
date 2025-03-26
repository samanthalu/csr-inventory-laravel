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
        Schema::dropIfExists('user_roles');
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id('role_id');
            $table->integer('role_user_id');
            $table->string('role_user_type');
            $table->boolean('role_read')->default(false)->nullable();
            $table->boolean('role_write')->default(false)->nullable();
            $table->boolean('role_edit')->default(false)->nullable();
            $table->boolean('role_delete')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
