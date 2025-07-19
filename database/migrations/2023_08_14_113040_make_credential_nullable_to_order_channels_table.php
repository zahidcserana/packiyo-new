<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeCredentialNullableToOrderChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_channels', function (Blueprint $table) {
            $table->string('credential_type')->nullable()->change();
            $table->unsignedBigInteger('credential_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('credential_id')->nullable(false)->change();
            $table->string('credential_type')->nullable(false)->change();
        });
    }
}
