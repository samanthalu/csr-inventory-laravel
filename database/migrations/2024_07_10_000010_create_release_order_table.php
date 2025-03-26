
<?php
        /**
     *namespace Database\Migrations;
     */


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class  extends Migration
{
        /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('release_order');
        Schema::create('release_order', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('rel_id');
            $table->dateTime('rel_date');
            $table->integer('rel_user_id');
            $table->integer('rel_approved_by')->nullable()->default(null);
            $table->string('rel_purpose');
            $table->string('rel_status', 45);
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('release_order');
    }
};
