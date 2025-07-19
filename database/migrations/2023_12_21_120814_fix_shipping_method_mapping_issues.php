<?php

use App\Models\EasypostCredential;
use App\Models\Order;
use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use App\Models\TribirdCredential;
use App\Models\WebshipperCredential;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixShippingMethodMappingIssues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*** CarrierCredential */
        $easypostCredentials = EasypostCredential::onlyTrashed()->get();

        foreach ($easypostCredentials as $easypostCredential) {
            $easypostCredential->shippingCarriers()->delete();
        }

        $webshipperCredentials = WebshipperCredential::onlyTrashed()->get();

        foreach ($webshipperCredentials as $webshipperCredential) {
            $webshipperCredential->shippingCarriers()->delete();
        }

        $tribirdCredentials = TribirdCredential::onlyTrashed()->get();

        foreach ($tribirdCredentials as $tribirdCredential) {
            $tribirdCredential->shippingCarriers()->delete();
        }

        /*** ShippingCarrier */
        $shippingCarriers = ShippingCarrier::onlyTrashed()->get();

        foreach ($shippingCarriers as $shippingCarrier) {
            $shippingCarrier->shippingMethods()->delete();
        }

        /*** ShippingMethod */
        $shippingMethods = ShippingMethod::onlyTrashed()->get();

        foreach ($shippingMethods as $shippingMethod) {
            Order::whereNull('archived_at')
                ->whereNull('cancelled_at')
                ->whereNull('fulfilled_at')
                ->where('shipping_method_id', $shippingMethod->id)
                ->update(['shipping_method_id' => null]);
            
            $shippingMethod->shippingMethodMappings()->delete();
            $shippingMethod->returnShippingMethodMappings()->delete();
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
