<?php

use App\Models\BulkShipBatch;
use App\Models\BulkShipBatchOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AddUniqueIndexToBulkShipBatchOrder extends Migration
{
    public function up(): void
    {
        // Before adding the unique index, we must first delete any duplicate rows
        $this->deleteDuplicates();

        Schema::table('bulk_ship_batch_order', function (Blueprint $table) {
            $table->unique(['bulk_ship_batch_id', 'order_id']);
        });
    }

    /**
     * Example of deleting duplicate rows from bulk_ship_batch_order table.
     *
     * Example table before deletion:
     *
     * | id  | bulk_ship_batch_id | order_id | shipment_id |
     * |-----|--------------------|----------|-------------|
     * | 1   | 1                  | 1        | 1           |
     * | 2   | 1                  | 1        | NULL        |
     * | 3   | 1                  | 2        | NULL        |
     * | 4   | 2                  | 3        | NULL        |
     * | 5   | 2                  | 3        | 2           |
     * | 6   | 2                  | 4        | 2           |
     * | 7   | 3                  | 5        | 3           |
     * | 8   | 3                  | 5        | 3           |
     * | 9   | 3                  | 6        | 3           |
     * | 10  | 4                  | 7        | NULL        |
     * | 11  | 4                  | 7        | NULL        |
     * | 12  | 4                  | 8        | NULL        |
     *
     * The rows that should be kept: 1, 4, 5
     *
     * Resulting table after deletion:
     *
     * | id  | bulk_ship_batch_id | order_id | shipment_id |
     * |-----|--------------------|----------|-------------|
     * | 1   | 1                  | 1        | 1           |
     * | 3   | 1                  | 2        | 1           |
     * | 5   | 2                  | 3        | 2           |
     * | 6   | 2                  | 4        | 3           |
     * | 7   | 3                  | 5        | 3           |
     * | 9   | 3                  | 6        | 3           |
     * | 10  | 4                  | 7        | NULL        |
     * | 12  | 4                  | 7        | NULL        |
     */
    private function deleteDuplicates(): void
    {
        BulkShipBatch::query()
            ->whereHas('orders', function ($query) {
                $query->select('orders.id')
                    ->groupBy('orders.id')
                    ->havingRaw('COUNT(*) > 1');
            })
            ->select('bulk_ship_batches.id')
            ->chunkById(100, function (Collection $batchesWithDuplicates) {
                foreach ($batchesWithDuplicates as $bulkShipBatch) {
                    $duplicates = BulkShipBatchOrder::query()
                        ->where('bulk_ship_batch_id', $bulkShipBatch->id)
                        ->groupBy('order_id')
                        ->havingRaw('COUNT(*) > 1')
                        ->get(['id', 'order_id']);

                    foreach ($duplicates as $duplicate) {
                        $bulkShipBatchOrders = BulkShipBatchOrder::query()
                            ->where('bulk_ship_batch_id', $bulkShipBatch->id)
                            ->where('order_id', $duplicate->order_id)
                            ->get();

                        $allHaveShipmentId = $bulkShipBatchOrders->every(fn (BulkShipBatchOrder $duplicate) => ! is_null($duplicate->shipment_id));
                        $allHaveNullShipmentId = $bulkShipBatchOrders->every(fn (BulkShipBatchOrder $duplicate) => is_null($duplicate->shipment_id));

                        if ($allHaveShipmentId) {
                            $bulkShipBatchOrders->sortBy('id')->skip(1)->each(fn (BulkShipBatchOrder $duplicate) => $duplicate->delete());
                        } elseif ($allHaveNullShipmentId) {
                            $bulkShipBatchOrders->sortBy('id')->skip(1)->each(fn (BulkShipBatchOrder $duplicate) => $duplicate->delete());
                        } else {
                            $lowestIdWithShipmentId = $bulkShipBatchOrders
                                ->filter(fn (BulkShipBatchOrder $duplicate) => ! is_null($duplicate->shipment_id))
                                ->min('id');
                            $bulkShipBatchOrders
                                ->filter(fn (BulkShipBatchOrder $duplicate) => $duplicate->id !== $lowestIdWithShipmentId)
                                ->each(fn (BulkShipBatchOrder $duplicate) => $duplicate->delete());
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        // In order to be able to drop the unique index, we must first drop the foreign key constraints
        DB::statement("ALTER TABLE bulk_ship_batch_order DROP FOREIGN KEY bulk_ship_batch_order_bulk_ship_batch_id_foreign;");
        DB::statement("ALTER TABLE bulk_ship_batch_order DROP FOREIGN KEY bulk_ship_batch_order_order_id_foreign;");

        Schema::table('bulk_ship_batch_order', function (Blueprint $table) {
            $table->dropUnique(['bulk_ship_batch_id', 'order_id']);

            // Add the foreign key constraints back
            $table->foreign('bulk_ship_batch_id')->references('id')->on('bulk_ship_batches')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }
}
