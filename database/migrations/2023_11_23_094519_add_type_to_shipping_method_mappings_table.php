<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToShippingMethodMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('shipping_method_mappings', static function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_method_id')->nullable()->change();
            $table->string('type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('shipping_method_mappings', static function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_method_id')->nullable(false)->change();
            $table->dropColumn('type');
        });

        Schema::enableForeignKeyConstraints();
    }
}
