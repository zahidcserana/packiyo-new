<?php

use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseIdToCustomerUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_user', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_id')->nullable()->after('role_id');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        $customerWarehouseMap = [];

        foreach (CustomerUser::whereNull('warehouse_id')->cursor() as $customerUser) {
            $user = User::find($customerUser->user_id);

            if ($user && !$user->isAdmin()) {
                $customerId = $customerUser->customer_id;
                $customer = Customer::find($customerId);

                if ($customer) {
                    $customerIds = [$customerId];

                    if ($customer->parent_id) {
                        $customerIds[] = $customer->parent_id;
                    }

                    $warehouseId = Arr::get($customerWarehouseMap, $customerId);

                    if (!$warehouseId) {
                        $warehouseId = Warehouse::whereIn('customer_id', $customerIds)
                            ->first()
                            ->id ?? null;
                    }

                    if ($warehouseId) {
                        $customerWarehouseMap[$customerId] = $warehouseId;

                        $customerUser->update([
                            'warehouse_id' => $warehouseId
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_user', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
}
