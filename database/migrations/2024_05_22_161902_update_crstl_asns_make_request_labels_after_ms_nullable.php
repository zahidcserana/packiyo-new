<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCrstlAsnsMakeRequestLabelsAfterMsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crstl_asns', function (Blueprint $table) {
            $table->float('request_labels_after_ms')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crstl_asns', function (Blueprint $table) {
            $table->float('request_labels_after_ms')->nullable(false)->change();
         });
     }
}
