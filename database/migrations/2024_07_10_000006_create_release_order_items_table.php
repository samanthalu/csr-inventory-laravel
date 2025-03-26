
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
        Schema::dropIfExists('release_order_items');
        Schema::create('release_order_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('roi_id');
            $table->integer('roi_rel_id');
            $table->integer('roi_product_id');
            $table->integer('roi_cat_id')->nullable()->default(null);
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('release_order_items');
    }
};
