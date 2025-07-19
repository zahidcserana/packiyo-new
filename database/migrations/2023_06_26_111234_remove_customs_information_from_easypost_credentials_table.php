<?php

use App\Models\CustomerSetting;
use App\Models\EasypostCredential;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCustomsInformationFromEasypostCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $easypostCredentials = EasypostCredential::all();

        foreach ($easypostCredentials as $easypostCredential) {
            app('customer')->storeSettings($easypostCredential->customer, [
                CustomerSetting::CUSTOMER_SETTING_CONTENTS_TYPE => $easypostCredential->contents_type,
                CustomerSetting::CUSTOMER_SETTING_CUSTOMS_DESCRIPTION => $easypostCredential->contents_explanation,
                CustomerSetting::CUSTOMER_SETTING_CUSTOMS_SIGNER => $easypostCredential->customs_signer,
                CustomerSetting::CUSTOMER_SETTING_EEL_PFC => $easypostCredential->eel_pfc,
            ]);
        }

        Schema::table('easypost_credentials', static function (Blueprint $table) {
            $table->dropColumn(['contents_type', 'contents_explanation', 'customs_signer', 'eel_pfc']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('easypost_credentials', static function (Blueprint $table) {
            $table->string('contents_type')->nullable();
            $table->string('contents_explanation')->nullable();
            $table->string('customs_signer')->nullable();
            $table->string('eel_pfc')->nullable();
        });
    }
}
