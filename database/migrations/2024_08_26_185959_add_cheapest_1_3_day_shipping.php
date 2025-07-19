<?php

use App\Models\Customer;
use App\Models\ShippingMethodMapping;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheapest13DayShipping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // rename cheapest-5days to cheapest-3-5days
        $shippingMethodMappings = ShippingMethodMapping::where('type', 'cheapest-5days')->get();

        foreach ($shippingMethodMappings as $shippingMethodMapping) {
            $shippingMethodMapping->update([
                'type' => 'cheapest-3-5days'
            ]);
        }

        // trigger Customer saved event so that we cheapest method mapping would be created
        $customers = Customer::whereNull('parent_id')->get();

        foreach ($customers as $customer) {
            $customer->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
