<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobTimestampColumnsToBulkShipBatchOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bulk_ship_batch_order', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->dropColumn(['shipped', 'errors']);

            DB::delete('DELETE `bsbo` FROM `bulk_ship_batch_order` `bsbo` LEFT JOIN `bulk_ship_batches` `bsb` ON `bsb`.`id` = `bsbo`.`bulk_ship_batch_id` WHERE `bsb`.`id` IS NULL');

            $table->foreign('bulk_ship_batch_id')
                ->references('id')
                ->on('bulk_ship_batches')
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
        Schema::table('bulk_ship_batch_order', function (Blueprint $table) {
            $table->dropForeign(['bulk_ship_batch_id']);
            $table->dropColumn(['started_at', 'finished_at']);
            $table->tinyInteger('shipped')->default(0);
            $table->tinyInteger('errors')->default(0);
        });
    }
}
