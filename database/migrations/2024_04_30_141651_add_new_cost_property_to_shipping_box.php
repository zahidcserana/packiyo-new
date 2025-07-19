<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewCostPropertyToShippingBox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_boxes', function (Blueprint $table) {
            $table->decimal('cost', 12)->nullable()->default(null)->after('barcode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_boxes', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
}
