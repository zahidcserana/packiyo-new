<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetDefaultQuantityColumnsValueToZeroOnLotItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lot_items', function (Blueprint $table) {
            $table->integer('quantity_added')->default(0)->change();
            $table->integer('quantity_removed')->nullable(false)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lot_items', function (Blueprint $table) {
            DB::statement('ALTER TABLE lot_items ALTER COLUMN quantity_added DROP DEFAULT');
            $table->integer('quantity_removed')->nullable()->default(null)->change();
        });
    }
}
