
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
        Schema::dropIfExists('hire');
        Schema::create('hire', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('hr_id');
            $table->string('hr_source');
            $table->string('hr_item_category', 45);
            $table->integer('hr_quantity')->nullable()->default(null);
            $table->date('hr_date_from');
            $table->integer('hr_duration');
            $table->string('hr_request_status', 45)->default('0');
            $table->integer('hr_request_created_by');
            $table->integer('hr_request_approved_by')->nullable()->default(null);
            $table->dateTime('hr_date_approved')->nullable()->default(null);
            $table->dateTime('hr_request_create_date')->nullable()->default(null);
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('hire');
    }
};
