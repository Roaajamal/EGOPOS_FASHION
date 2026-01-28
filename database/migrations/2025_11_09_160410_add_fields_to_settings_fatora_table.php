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
        Schema::table('settings_fatora', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('settings_fatora', 'supplier_income_source')) {
                $table->string('supplier_income_source', 50)->nullable()->after('secret_key')->comment('تسلسل مصدر الدخل - Required');
            }
            if (!Schema::hasColumn('settings_fatora', 'tin')) {
                $table->string('tin', 50)->nullable()->after('supplier_income_source')->comment('الرقم الضريبي - Tax Identification Number');
            }
            if (!Schema::hasColumn('settings_fatora', 'city_code')) {
                $table->string('city_code', 10)->nullable()->after('city_name')->comment('e.g., JO-AM for Amman');
            }
            if (!Schema::hasColumn('settings_fatora', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('county');
            }
            if (!Schema::hasColumn('settings_fatora', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('csr');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings_fatora', function (Blueprint $table) {
            $table->dropColumn(['supplier_income_source', 'tin', 'city_code', 'postal_code', 'is_active']);
        });
    }
};
