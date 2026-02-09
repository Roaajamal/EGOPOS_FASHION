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
        Schema::table('business_locations', function (Blueprint $table) {
            $table->integer('currency_id')->unsigned()->nullable()->after('business_id');
        
            // ربط الحقل كـ Foreign Key مع جدول العملات
            $table->foreign('currency_id')->references('id')->on('currencies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_locations', function (Blueprint $table) {
            // حذف العلاقة أولاً ثم حذف العمود في حال تراجعنا عن الـ Migration
        $table->dropForeign(['currency_id']);
        $table->dropColumn('currency_id');
        });
    }
};
