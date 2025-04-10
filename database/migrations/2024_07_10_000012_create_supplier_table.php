
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
        Schema::dropIfExists('suppliers');
        Schema::create('suppliers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('sup_id');
            $table->string('sup_name', 100);
            $table->string('sup_address')->nullable()->default(null);
            $table->string('sup_phone', 45);
            $table->string('sup_email', 145)->nullable()->default(null);
            $table->string('sup_district', 40)->nullable()->default(null);

            $table->string('sup_type')->nullable();
            $table->string('sup_tax_id')->nullable();
            $table->string('sup_contact_person')->nullable();
            $table->string('sup_contact_phone')->nullable();
            $table->string('sup_bank_details')->nullable();
            $table->string('sup_registration_number')->nullable();
            $table->nullableTimestamps();
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
};
