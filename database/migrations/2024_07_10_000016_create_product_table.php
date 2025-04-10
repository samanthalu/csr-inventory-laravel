
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
        Schema::dropIfExists('products');
        Schema::create('products', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('prod_id');
            $table->string('prod_name', 100);
            $table->string('prod_serial_num', 100)->unique();
            $table->string('prod_model_number', 50)->nullable()->default(null);
            $table->string('prod_batch_number', 45)->nullable();
            $table->string('prod_desc')->nullable()->default(null);
            $table->string('prod_tag_number')->nullable();
            $table->integer('prod_funding')->default(0);
            $table->integer('prod_quantity')->nullable()->default(null);
            $table->string('prod_quantity_measure', 40);
            $table->decimal('prod_cost', 10, 2)->nullable()->default(null);
            $table->string('prod_other_identifier')->nullable();

            $table->unsignedInteger('cat_id');
            $table->foreign('cat_id')->references('cat_id')->on('category')->onDelete('restrict');
           
            $table->unsignedInteger('sup_id');
            $table->foreign('sup_id')->references('sup_id')->on('suppliers')->onDelete('restrict');
           
            $table->integer('order_id')->nullable()->default(null);
            $table->date('prod_purchase_date')->nullable()->default(null);
            
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
           
            $table->text('prod_notes')->nullable();
            $table->date('prod_warranty_expire')->nullable();

            $table->string('prod_condition')->nullable();
            $table->string('prod_current_status')->nullable();
            $table->timestamps();
        });
 Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['user_id']); // Drop the foreign key
        });

        Schema::dropIfExists('products');
    }
};
