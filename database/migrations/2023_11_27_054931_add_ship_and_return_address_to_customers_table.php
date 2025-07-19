<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShipAndReturnAddressToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('ship_from_contact_information_id')->nullable();
            $table->unsignedInteger('return_to_contact_information_id')->nullable();

            $table->foreign('ship_from_contact_information_id')
            ->references('id')
            ->on('contact_informations')
            ->onUpdate('cascade')
            ->onDelete('cascade');

            $table->foreign('return_to_contact_information_id')
            ->references('id')
            ->on('contact_informations')
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
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['ship_from_contact_information_id']);
            $table->dropColumn('ship_from_contact_information_id');

            $table->dropForeign(['return_to_contact_information_id']);
            $table->dropColumn('return_to_contact_information_id');
        });
    }
}
