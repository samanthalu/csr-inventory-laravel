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
        Schema::table('category', function (Blueprint $table) {
            // Whether this category appears as a tab on the Assets inventory page.
            // Opt-in: categories start hidden until explicitly enabled.
            $table->boolean('cat_show_in_tabs')->default(false)->after('cat_desc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category', function (Blueprint $table) {
            $table->dropColumn('cat_show_in_tabs');
        });
    }
};
