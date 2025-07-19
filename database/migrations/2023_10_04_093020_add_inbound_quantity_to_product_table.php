<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInboundQuantityToProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            $qtyInbound = Schema::hasColumn('products', 'quantity_inbound');

            if (!$qtyInbound) {
                $table->integer('quantity_inbound')->default(0);
                $table->index('quantity_inbound');
            }
        });

        Schema::table('product_warehouse', static function (Blueprint $table) {
            $table->integer('quantity_inbound')->default(0);
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
            $qtyInbound = Schema::hasColumn('products', 'quantity_inbound');

            if ($qtyInbound) {
                $table->dropIndex(['quantity_inbound']);
                $table->dropColumn('quantity_inbound');
            }
        });

        Schema::table('product_warehouse', static function (Blueprint $table) {
            $table->dropColumn('quantity_inbound');
        });
    }
}
