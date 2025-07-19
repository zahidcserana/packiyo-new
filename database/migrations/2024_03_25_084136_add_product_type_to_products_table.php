<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductTypeToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->string('type')->after('country_of_origin')->nullable();
        });

        $kitProducts = Product::whereHas('kitItems')->get();
        $updatedProducts = [];

        foreach ($kitProducts as $product) {
            $updatedProducts[] = [
                'id' => $product->id,
                'type' => Product::PRODUCT_TYPE_STATIC_KIT
            ];
        }

        foreach (array_chunk($updatedProducts, 100) as $chunk) {
            Product::query()->upsert($chunk, 'type');
        }

        $updatedProducts = [];

        $regularProducts = Product::whereNull('type')->get();

        foreach ($regularProducts as $product) {
            $updatedProducts[] = [
                'id' => $product->id,
                'type' => Product::PRODUCT_TYPE_REGULAR
            ];
        }

        foreach (array_chunk($updatedProducts, 100) as $chunk) {
            Product::query()->upsert($chunk, 'type');
        }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
