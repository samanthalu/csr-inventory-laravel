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
        Schema::table('hire_rates', function (Blueprint $table) {
            $table->decimal('hr_rate_per_week', 10, 2)->nullable()->after('hr_rate');
            $table->decimal('hr_rate_per_month', 10, 2)->nullable()->after('hr_rate_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('hire_rates', function (Blueprint $table) {
            $table->dropColumn(['hr_rate_per_week', 'hr_rate_per_month']);
        });
    }
};
