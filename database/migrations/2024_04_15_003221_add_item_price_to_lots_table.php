<?php

use App\Models\Lot;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddItemPriceToLotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migration failed on some instances.
        if (!Schema::hasColumn('lots', 'item_price')) {
            Schema::table('lots', function (Blueprint $table) {
                $table->decimal('item_price', 12)->nullable();
            });
        }

        $lots = Lot::all();

        foreach ($lots as $lot) {
            if ($product = $lot->product) {
                $lot->item_price = $product->price;
                $lot->save();
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
        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn('item_price');
        });
    }
}
