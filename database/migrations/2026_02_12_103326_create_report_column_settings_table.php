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
        Schema::create('report_column_settings', function (Blueprint $table) {
           $table->id();
           $table->string('report_key')->index(); // اسم التقرير (مثلاً: missing_products)
           $table->string('column_key');          // اسم العمود (مثلاً: brand)
           $table->text('role_ids');              // مصفوفة IDs الأدوار المسموح لها (JSON)
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
        Schema::dropIfExists('report_column_settings');
    }
};
