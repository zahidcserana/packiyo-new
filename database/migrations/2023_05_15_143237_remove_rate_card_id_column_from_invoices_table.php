<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveRateCardIdColumnFromInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('invoices', static function (Blueprint $table) {
            $table->dropForeign('invoices_rate_card_id_foreign');
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
        Schema::table('invoices', static function (Blueprint $table) {
            $table->unsignedInteger('rate_card_id')->after('id')->nullable();

            $table->foreign('rate_card_id')
                ->references('id')
                ->on('rate_cards')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
}
