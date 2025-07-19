<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrstlPackingLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crstl_packing_labels', function (Blueprint $table) {
            $table->id();

            $table->string('label_type');
            $table->text('signed_url');
            $table->dateTimeTz('signed_url_expires_at');

            $table->unsignedBigInteger('asn_id');
            $table->foreign('asn_id')
                ->references('id')
                ->on('crstl_asns');

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
        Schema::dropIfExists('crstl_packing_labels');
    }
}
