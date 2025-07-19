<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdersShippedToBulkShipBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bulk_ship_batches', function (Blueprint $table) {
            $table->unsignedInteger('orders_shipped')->default(0)->index();
        });

        DB::update('UPDATE `bulk_ship_batches` `bsb`
            LEFT JOIN
                (SELECT `bulk_ship_batch_id`, COUNT(`shipment_id`) AS `shipment_count` FROM `bulk_ship_batch_order` `bsbo`
                          WHERE `shipment_id` IS NOT NULL
                          GROUP BY `bsbo`.`bulk_ship_batch_id`) `bsbo_summed`
                ON `bsb`.`id` = `bsbo_summed`.`bulk_ship_batch_id`
            SET `bsb`.`orders_shipped` = IFNULL(`bsbo_summed`.`shipment_count`, 0)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bulk_ship_batches', function (Blueprint $table) {
            $table->dropIndex(['orders_shipped']);
            $table->dropColumn('orders_shipped');
        });
    }
}
