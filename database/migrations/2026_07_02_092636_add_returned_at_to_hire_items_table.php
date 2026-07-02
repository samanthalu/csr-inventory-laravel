<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hire_items', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('is_returned');
        });

        // Backfill: existing returned items get the hire's planned return date as a
        // best-available actual-return timestamp.
        DB::statement("
            UPDATE hire_items hi
            JOIN hires h ON h.id = hi.hire_id
            SET hi.returned_at = h.hire_return_date
            WHERE hi.is_returned = 1 AND hi.returned_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('hire_items', function (Blueprint $table) {
            $table->dropColumn('returned_at');
        });
    }
};
