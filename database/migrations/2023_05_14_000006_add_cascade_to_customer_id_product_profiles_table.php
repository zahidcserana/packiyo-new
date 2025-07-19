<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCascadeToCustomerIdProductProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_profiles', function (Blueprint $table) {
            $table->dropForeign('product_profiles_customer_id_foreign');

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_profiles', function (Blueprint $table) {
            Schema::table('product_profiles', function (Blueprint $table) {
                $table->dropForeign('product_profiles_customer_id_foreign');

                $table->foreign('customer_id')
                    ->references('id')
                    ->on('customers');
            });
        });
    }
}
