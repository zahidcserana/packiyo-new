<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntegrationToShippingCarriersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_carriers', function (Blueprint $table) {
            $table->string('integration')->nullable();
            $table->text('carrier_account')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_carriers', function (Blueprint $table) {
            $table->string('carrier_account')->nullable()->change();
            $table->dropColumn('integration');
        });
    }
}
