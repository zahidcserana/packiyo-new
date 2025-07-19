<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdiProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edi_providers', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();

            $table->string('name');
            $table->boolean('active')->default(true);

            // For Crstl subtype - thus all nullable.
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->dateTimeTz('access_token_expires_at')->nullable();
            $table->boolean('is_multi_crstl_org')->nullable();
            $table->string('external_role')->nullable();
            $table->string('external_organization_id')->nullable();

            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers');

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edi_providers');
    }
}
