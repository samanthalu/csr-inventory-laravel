
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
        Schema::dropIfExists('hire_rates');
        Schema::create('hire_rates', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('hr_id');
            $table->integer('hr_item_category');
            $table->decimal('hr_rate', 10, 2);
            $table->nullableTimestamps();
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('hire_rates');
    }
};
