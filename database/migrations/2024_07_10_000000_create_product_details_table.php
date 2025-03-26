
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
        Schema::dropIfExists('product_details');
        Schema::create('product_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('pd_id');
            $table->integer('pd_prod_id');
            $table->string('pd_serial_num')->nullable()->default(null);
            $table->string('pd_prod_num')->nullable()->default(null);
            $table->string('pd_other_identifier')->nullable()->default(null);
            $table->float('pd_cost')->nullable();
            $table->string('pd_available', 45)->nullable();
            $table->string('pd_current_status', 145)->nullable();
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('product_details');
    }
};
