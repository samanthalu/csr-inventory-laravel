
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
        Schema::dropIfExists('category');
        Schema::create('category', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('cat_id');
            $table->string('cat_name', 45);
            $table->string('cat_desc')->nullable();
            $table->string('cat_hireable')->nullable();
            $table->string('cat_slug')->nullable();
            $table->string('cat_status')->nullable();
            $table->timestamps();
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('category');
    }
};
