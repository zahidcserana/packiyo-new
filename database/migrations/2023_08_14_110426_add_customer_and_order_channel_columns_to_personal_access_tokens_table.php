<?php

use App\Models\OrderChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerAndOrderChannelColumnsToPersonalAccessTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedInteger('customer_id')
                ->nullable()
                ->after('source');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignIdFor(OrderChannel::class)
                ->nullable()
                ->after('customer_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['order_channel_id']);
            $table->dropColumn('order_channel_id');

            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
}
