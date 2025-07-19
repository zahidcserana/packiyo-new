<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDimensionsToShippingBoxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_boxes', function (Blueprint $table) {
            $table->decimal('height', 12, 4)->change();
            $table->decimal('length', 12, 4)->change();
            $table->decimal('width', 12, 4)->change();
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
            $table->decimal('height', 12, 2)->change();
            $table->decimal('length', 12, 2)->change();
            $table->decimal('width', 12, 2)->change();
        });
    }
}
