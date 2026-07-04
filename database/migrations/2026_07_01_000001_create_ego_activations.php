<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 🆕 جدول تفعيل النظام لكل بزنس (الأحدث = التفعيل الحالي)
class CreateEgoActivations extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('ego_activations')) {
            Schema::create('ego_activations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedInteger('duration_value')->default(1);
                $table->enum('duration_unit', ['month', 'year'])->default('month');
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'end_date'], 'ego_activation_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ego_activations');
    }
}
