<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('settings_fatora')) {
        Schema::create('settings_fatora', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->string('client_id')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('supplier_income_source', 50)->nullable()->comment('تسلسل مصدر الدخل - Required');
            $table->string('tin', 50)->nullable()->comment('الرقم الضريبي - Tax Identification Number');
            $table->string('registration_name')->nullable()->comment('اسم الشركة المسجل');
            $table->string('crn', 50)->nullable()->comment('Commercial Registration Number');
            $table->string('street_name')->nullable();
            $table->string('building_number', 50)->nullable();
            $table->string('city_name', 100)->nullable();
            $table->string('city_code', 10)->nullable()->comment('e.g., JO-AM for Amman');
            $table->string('county', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('plot_al_zone', 100)->nullable();
            $table->string('vat', 50)->nullable();
            $table->text('csr')->nullable()->comment('Certificate Signing Request');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique('business_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings_fatora');
    }
};
