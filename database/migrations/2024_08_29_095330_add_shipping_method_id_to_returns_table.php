<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShippingMethodIdToReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_method_id')->nullable();

            $table->foreign('shipping_method_id')
                ->references('id')
                ->on('shipping_methods');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('returns', function (Blueprint $table) {
            $table->dropForeign('returns_shipping_method_id_foreign');
            $table->dropColumn('shipping_method_id');
        });
    }
}
