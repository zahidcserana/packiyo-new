<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRateCardIdOnCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('customers', static function (Blueprint $table) {
            $table->dropForeign('customers_rate_card_id_foreign');
            $table->dropColumn('rate_card_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('customers', static function (Blueprint $table) {
            $table->unsignedInteger('rate_card_id')->after('id')->nullable();

            $table->foreign('rate_card_id')
                ->references('id')
                ->on('rate_cards')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
}
