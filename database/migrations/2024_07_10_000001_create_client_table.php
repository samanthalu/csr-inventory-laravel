
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
        Schema::dropIfExists('client');
        Schema::create('client', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('client_id');
            $table->string('client_name', 100);
            $table->string('client_address', 45);
            $table->string('client_phone', 45);
            $table->string('client_email', 45)->nullable()->default(null);
            $table->string('client_district', 45)->nullable()->default(null);
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('client');
    }
};
