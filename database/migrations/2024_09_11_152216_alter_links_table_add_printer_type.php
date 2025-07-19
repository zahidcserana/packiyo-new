<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\UserSetting;
use Illuminate\Support\Facades\DB;

class AlterLinksTableAddPrinterType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('links', function (Blueprint $table) {
            $table->string('printer_type')
                ->default(UserSetting::USER_SETTING_LABEL_PRINTER_ID)
                ->after('is_printable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn('printer_type');
        });
    }
}
