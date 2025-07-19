<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRanByAutomationColumnToAuditsTable extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('ran_by_automation_id')->nullable()->constrained('automations');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['ran_by_automation_id']);
            $table->dropColumn('ran_by_automation_id');
        });
    }
}
