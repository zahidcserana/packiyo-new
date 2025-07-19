<?php

use App\Models\ShippingBox;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBarcodeToShippingBoxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping_boxes', function (Blueprint $table) {
            $table->string('barcode')->nullable();
        });

        foreach (ShippingBox::cursor() as $shippingBox) {
            $shippingBox->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_boxes', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });
    }
}
