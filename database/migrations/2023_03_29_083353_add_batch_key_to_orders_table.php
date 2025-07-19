<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatchKeyToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', static function (Blueprint $table) {
            $table->string('batch_key', 8192)->nullable()->index();
        });

        DB::table('bulk_ship_batch_order')
            ->whereNull('shipment_id')
            ->whereNull('started_at')
            ->whereNull('finished_at')
            ->whereNull('status_message')
            ->where('labels_merged', 0)
            ->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', static function (Blueprint $table) {
            $table->dropIndex(['batch_key']);
            $table->dropColumn('batch_key');
        });
    }
}
