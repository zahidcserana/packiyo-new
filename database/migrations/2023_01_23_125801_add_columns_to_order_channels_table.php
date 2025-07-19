<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToOrderChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_channels', function (Blueprint $table) {
            $table->string('ecommerce_image_url')->nullable();
            $table->morphs('credential');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_channels', function (Blueprint $table) {
            $table->dropColumn('ecommerce_image_url');
            $table->dropMorphs('credential');
        });
    }
}
