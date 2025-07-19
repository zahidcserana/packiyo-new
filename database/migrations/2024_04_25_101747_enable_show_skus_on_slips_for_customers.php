<?php

use App\Models\Customer;
use App\Models\CustomerSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnableShowSkusOnSlipsForCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $customers = Customer::all();

        foreach ($customers as $customer) {
            CustomerSetting::create([
                'customer_id' => $customer->id,
                'key' => 'show_skus_on_slips',
                'value' => 1
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        CustomerSetting::where('key', 'show_skus_on_slips')->delete();
    }
}
