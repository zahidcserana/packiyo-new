<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBulkShipPickableColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('bulk_ship_pickable')->nullable();
            $table->boolean('bulk_ship_pickable_effective')->nullable();
        });

        Schema::table('location_types', function (Blueprint $table) {
            $table->boolean('bulk_ship_pickable')->nullable();
        });

        DB::update('UPDATE `locations` SET `bulk_ship_pickable` = 0');
        DB::update('UPDATE `locations` SET `bulk_ship_pickable_effective` = 0');
        DB::update('UPDATE `location_types` SET `bulk_ship_pickable` = 0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['bulk_ship_pickable', 'bulk_ship_pickable_effective']);
        });

        Schema::table('location_types', function (Blueprint $table) {
            $table->dropColumn('bulk_ship_pickable');
        });
    }
}
