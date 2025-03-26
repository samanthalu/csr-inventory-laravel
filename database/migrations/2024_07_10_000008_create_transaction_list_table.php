
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
        Schema::dropIfExists('transaction_list');
        Schema::create('transaction_list', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('tl_id');
            $table->integer('tl_trans_id');
            $table->integer('tl_prod_id');
            $table->integer('tl_quantity')->nullable()->default(null);
            $table->string('tl_action');
            $table->decimal('tl_total', 10, 2)->nullable()->default(null);
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('transaction_list');
    }
};
