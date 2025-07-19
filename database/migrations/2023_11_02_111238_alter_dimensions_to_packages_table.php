<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDimensionsToPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('weight', 12, 4)->change();
            $table->decimal('height', 12, 4)->change();
            $table->decimal('length', 12, 4)->change();
            $table->decimal('width', 12, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('weight', 12, 2)->change();
            $table->decimal('height', 12, 2)->change();
            $table->decimal('length', 12, 2)->change();
            $table->decimal('width', 12, 2)->change();
        });
    }
}
