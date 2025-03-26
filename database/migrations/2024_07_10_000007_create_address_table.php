
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
        Schema::dropIfExists('address');
        Schema::create('address', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('add_id');
            $table->integer('add_client_id');
            $table->string('add_name')->nullable();
            $table->string('add_phone1', 45)->nullable();
            $table->string('add_phone2', 45)->nullable();
            $table->string('add_email', 45)->nullable();
            $table->string('add_line1')->nullable();
            $table->string('add_line2')->nullable();
            $table->string('add_district', 45)->nullable();
            $table->string('add_city', 45)->nullable();
            $table->string('add_country', 45)->nullable();
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('address');
    }
};
