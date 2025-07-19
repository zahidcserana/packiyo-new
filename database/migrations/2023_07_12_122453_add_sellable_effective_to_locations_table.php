<?php

use App\Models\Location;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSellableEffectiveToLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('locations', static function (Blueprint $table) {
            $table->boolean('sellable_effective')->after('sellable')->default(0);
        });

        Location::chunk(100, static function ($locations) {
            DB::beginTransaction();

            try {
                foreach ($locations as $location) {
                    if (is_null($location->locationType)) {
                        $sellableEffective = $location->sellable;
                    } else {
                        $sellableEffective = $location->locationType->sellable ?? $location->sellable;
                    }

                    DB::table('locations')
                        ->where('id', $location->id)
                        ->update([
                            'sellable_effective' => $sellableEffective
                        ]);
                }
            } catch (Exception $e) {
                DB::rollback();
                throw $e;
            }
            DB::commit();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('locations', static function (Blueprint $table) {
            $table->dropColumn('sellable_effective');
        });
    }
}
