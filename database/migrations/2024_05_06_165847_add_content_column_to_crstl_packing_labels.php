<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContentColumnToCrstlPackingLabels extends Migration
{
    public function up(): void
    {
        Schema::table('crstl_packing_labels', function (Blueprint $table) {
            $table->binary('content')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('crstl_packing_labels', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
}
