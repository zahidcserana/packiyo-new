<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantityBackorderedSumToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('quantity_backordered_sum')
                ->after('quantity_allocated_pickable_sum')
                ->default(0)
                ->index();
        });

        $productCursor = Product::where('quantity_allocated', '>', 0)
            ->orWhere('quantity_backordered', '>', 0)
            ->cursor();

        foreach ($productCursor as $product) {
            dump($product->sku);
            app('product')->allocateInventory($product);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['quantity_backordered_sum']);
            $table->dropColumn('quantity_backordered_sum');
        });
    }
}
