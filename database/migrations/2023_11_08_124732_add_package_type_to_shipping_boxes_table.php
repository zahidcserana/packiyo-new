<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackageTypeToShippingBoxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_boxes', function (Blueprint $table) {
            $table->decimal('weight', 12, 4);
            $table->boolean('weight_locked')->default(0)->nullable();
            $table->string('type')->nullable();
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
            $table->dropColumn('weight');
            $table->dropColumn('weight_locked');
            $table->dropColumn('type');
        });
    }
}
