<?php

use App\Models\Customer;
use App\Models\CustomerSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSandBoxColumnToEdiProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('edi_providers', function (Blueprint $table) {
            $table->boolean('is_sandbox')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('edi_providers', function (Blueprint $table) {
            $table->dropColumn('is_sandbox');
        });
    }
}
