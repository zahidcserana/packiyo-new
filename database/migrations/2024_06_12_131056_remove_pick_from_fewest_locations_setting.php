<?php

use App\Components\RouteOptimizationComponent;
use App\Models\Customer;
use App\Models\CustomerSetting;
use Illuminate\Database\Migrations\Migration;

class RemovePickFromFewestLocationsSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $customerSettings = CustomerSetting::where('key', 'pick_from_fewest_locations')
            ->where('value', '1')
            ->get();

        foreach ($customerSettings as $customerSetting) {
            app('customer')->storeSettings(Customer::find($customerSetting->customer_id), [
                CustomerSetting::CUSTOMER_SETTING_PICKING_ROUTE_STRATEGY => RouteOptimizationComponent::PICKING_STRATEGY_MOST_INVENTORY
            ]);

            $customerSetting->delete();
        }
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
