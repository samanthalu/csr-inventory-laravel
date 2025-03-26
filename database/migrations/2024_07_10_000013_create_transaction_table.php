
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
        Schema::dropIfExists('transaction');
        Schema::create('transaction', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('trans_id');
            $table->integer('trans_user_id');
            $table->dateTime('trans_date');
            $table->string('trans_status', 45)->nullable()->default(null);
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('transaction');
    }
};
