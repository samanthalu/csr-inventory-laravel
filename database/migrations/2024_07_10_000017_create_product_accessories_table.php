
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
        Schema::dropIfExists('product_accessories');
        Schema::create('product_accessories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('pa_id');
            $table->unsignedInteger('pa_prod_id'); // INT UNSIGNED
            $table->foreign('pa_prod_id')->references('prod_id')->on('product')->onDelete('cascade');
            $table->string('pa_name');
            $table->string('pa_serial_number');
            $table->string('pa_qty', 45)->nullable()->default(null);
            $table->string('pa_color')->nullable();
            $table->string('pa_desc', 45)->nullable()->default(null);
            $table->timestamps();
           
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('product_accessories');
    }
};
