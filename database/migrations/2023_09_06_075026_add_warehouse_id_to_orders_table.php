<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseIdToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $customers = Customer::all();

        foreach ($customers as $customer) {
            $customerIdForWarehouseSearch = $customer->id;

            if ($customer->parent_id) {
                $customerIdForWarehouseSearch = $customer->parent_id;
            }

            $warehouse = Warehouse::where('customer_id', $customerIdForWarehouseSearch)->first();

            if ($warehouse) {
                Order::where('customer_id', $customer->id)
                    ->whereNull('fulfilled_at')
                    ->whereNull('cancelled_at')
                    ->whereNull('warehouse_id')
                    ->update([
                        'warehouse_id' => $warehouse->id
                    ]);
            }
        }

        DB::update('UPDATE `orders` SET `batch_key` = NULL WHERE `batch_key` IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
}
