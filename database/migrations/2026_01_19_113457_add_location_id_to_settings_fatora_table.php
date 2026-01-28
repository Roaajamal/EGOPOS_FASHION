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
    public function up(): void
{
    Schema::table('settings_fatora', function (Blueprint $table) {
        // 1. حذف الـ Foreign Key أولاً (يجب استخدام اسم القيد في قاعدة البيانات)
        // عادة ما يكون الاسم: table_column_foreign
        $table->dropForeign(['business_id']);

        // 2. الآن يمكنك حذف الـ Unique Index بدون مشاكل
        $table->dropUnique(['business_id']);

        // 3. إضافة عمود location_id الجديد وجعله Unique
        $table->unsignedInteger('location_id')
              ->nullable()
              ->unique()
              ->after('business_id');

        // 4. إعادة بناء الـ Foreign Key لـ business_id (بدون Unique هذه المرة)
        $table->foreign('business_id')
              ->references('id')
              ->on('business')
              ->onDelete('cascade');

        // 5. بناء الـ Foreign Key للعمود الجديد location_id
        $table->foreign('location_id')
              ->references('id')
              ->on('business_locations')
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
        Schema::table('settings_fatora', function (Blueprint $table) {
            // التراجع عن التغييرات في حال عمل Rollback
            $table->dropForeign(['location_id']);
            $table->dropUnique(['location_id']);
            $table->dropColumn('location_id');
            
            // إعادة الـ Unique للـ business_id (لإرجاع الحالة كما كانت)
            $table->unique('business_id');
        });
    }
};
