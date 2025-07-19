<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameEcommerceImageUrlToOrderChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_channels', function (Blueprint $table) {
            $table->renameColumn('ecommerce_image_url', 'image_url');
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
            $table->renameColumn('image_url', 'ecommerce_image_url');
        });
    }
}
