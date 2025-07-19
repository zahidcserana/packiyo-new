<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUseNativeTrackingUrlsToEasypostCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('easypost_credentials', function (Blueprint $table) {
            $table->boolean('use_native_tracking_urls')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('easypost_credentials', function (Blueprint $table) {
            $table->dropColumn('use_native_tracking_urls');
        });
    }
}
