<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackageDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_documents', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('package_id')->nullable();
            $table->foreign('package_id')
                ->references('id')
                ->on('packages')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('size')->nullable();
            $table->text('url')->nullable();
            $table->binary('content')->nullable();
            $table->string('document_type')->nullable();
            $table->string('type')->nullable();
            $table->boolean('submitted_electronically')->default(0);
            $table->boolean('print_with_label')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE `package_documents` CHANGE `content` `content` LONGBLOB NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('package_documents');
    }
}
