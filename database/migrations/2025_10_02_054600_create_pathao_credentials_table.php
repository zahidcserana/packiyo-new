<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePathaoCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pathao_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->string('api_base_url');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('username');
            $table->string('password');
            $table->unsignedInteger('store_id');
            $table->timestamps();

            $table->softDeletes();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pathao_credentials');
    }
}
