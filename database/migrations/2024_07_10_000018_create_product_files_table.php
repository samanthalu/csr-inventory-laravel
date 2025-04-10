
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
        Schema::dropIfExists('product_files');
        Schema::create('product_files', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('pf_id');
            $table->unsignedInteger('pf_prod_id'); // INT UNSIGNED
            $table->foreign('pf_prod_id')->references('prod_id')->on('products')->onDelete('cascade');
            $table->string('pf_file_name')->nullable();
            $table->unsignedBigInteger('pf_file_size')->nullable();
            $table->string('pf_file_path')->nullable();
            $table->string('pf_file_type', 45)->nullable();
            $table->timestamps();
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('product_files');
    }
};
