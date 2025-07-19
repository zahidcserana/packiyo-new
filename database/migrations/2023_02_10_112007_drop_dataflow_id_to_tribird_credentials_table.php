<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDataflowIdToTribirdCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tribird_credentials', function (Blueprint $table) {
            $table->dropColumn('dataflow_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tribird_credentials', function (Blueprint $table) {
            $table->integer('dataflow_id');
        });
    }
}
