<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantityRemainingColumnToLotItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lot_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity_remaining')
                ->after('quantity_removed')
                ->default(0)
                ->index();
        });

        DB::update('UPDATE `lot_items` SET `quantity_removed` = `quantity_added` WHERE `quantity_removed` > `quantity_added`');
        DB::update('UPDATE `lot_items` SET `quantity_remaining` = `quantity_added` - `quantity_removed`');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lot_items', function (Blueprint $table) {
            $table->dropIndex(['quantity_remaining']);
            $table->dropColumn('quantity_remaining');
        });
    }
}
