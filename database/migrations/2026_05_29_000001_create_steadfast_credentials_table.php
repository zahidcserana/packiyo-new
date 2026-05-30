<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSteadfastCredentialsTable extends Migration
{
    public function up()
    {
        Schema::create('steadfast_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->string('api_base_url')->default('https://portal.steadfast.com.bd/api/v1');
            $table->string('api_key');
            $table->string('secret_key');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('steadfast_credentials');
    }
}
