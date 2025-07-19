<?php

use App\Models\BulkShipBatch;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;

class RemoveEmptyBatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $orders = Order::query()->where('created_at', '>', '2024-07-01 00:00:00')->get();
        foreach ($orders as $order) {
            $batchKey = $order->batch_key;

            if ($batchKey === null) {
                continue;
            }

            $allValuesAreZero = $this->checkIfAllValuesAreZero($batchKey);

            if ($allValuesAreZero) {
                $order->batch_key = null;
                $order->save();
            }
        }

        $bulkShipBatches = BulkShipBatch::query()
            ->where('created_at', '>', '2024-07-01 00:00:00')
            ->where('in_progress', 0)
            ->whereNull('shipped_at')
            ->where('orders_shipped', 0)
            ->get();

        foreach ($bulkShipBatches as $bulkShipBatch) {
            $batchKey = $bulkShipBatch->batch_key;

            if ($batchKey === null) {
                continue;
            }

            $allValuesAreZero = $this->checkIfAllValuesAreZero($batchKey);

            if ($allValuesAreZero) {
                $bulkShipBatch->delete();
            }
        }
    }

    private function checkIfAllValuesAreZero(string $batchKey): bool
    {
        $batchKeyValues = explode(',', $batchKey);
        $allValuesAreZero = true;

        foreach ($batchKeyValues as $batchKeyValue) {
            $batchKeyValueParts = explode(':', $batchKeyValue);
            if ($batchKeyValueParts[1] !== '0') {
                $allValuesAreZero = false;
                break;
            }
        }

        return $allValuesAreZero;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
