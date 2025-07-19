<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropIsKitColumnOnProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->dropColumn(['is_kit', 'kit_type']);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $table->boolean('is_kit')->default(false);
            $table->string('kit_type')->default(0);
        });

        $kitProducts = Product::whereHas('kitItems')->get();
        $updatedProducts = [];

        foreach ($kitProducts as $product) {
            $updatedProducts[] = [
                'id' => $product->id,
                'kit_type' => 1,
                'is_kit' => true,
            ];
        }

        Product::query()->upsert($updatedProducts, ['kit_type', 'is_kit']);
    }
}
