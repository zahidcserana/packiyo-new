<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebhookJobsFailuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webhook_jobs_failures', function (Blueprint $table) {
            $table->id();
            $table->uuid('job_uuid')->unique();
            $table->integer('attempt_count');
            $table->enum('failed_type', ['HTTP-400-Error', 'HTTP-500-Error']); // Adjust enum values as needed
            $table->integer('count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhook_jobs_failures');
    }
}
