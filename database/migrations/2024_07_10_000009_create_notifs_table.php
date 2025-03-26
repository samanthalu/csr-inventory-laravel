
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
        Schema::dropIfExists('notifs');
        Schema::create('notifs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('notif_id');
            $table->string('notif', 245);
            $table->integer('notif_by');
            $table->integer('notif_to');
            $table->dateTime('notif_date');
            $table->tinyInteger('notif_status')->default('0');
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('notifs');
    }
};
