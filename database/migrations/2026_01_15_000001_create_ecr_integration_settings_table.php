<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcrIntegrationSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('ecr_integration_settings', function (Blueprint $table) {
            $table->id();

            // العلاقة مع Business و Location
            $table->unsignedInteger('business_id'); // تغيير من unsignedBigInteger إلى unsignedInteger
            $table->unsignedInteger('business_location_id'); // تغيير من unsignedBigInteger إلى unsignedInteger

            $table->foreign('business_id')
                  ->references('id')
                  ->on('business')
                  ->onDelete('cascade');

            $table->foreign('business_location_id')
                  ->references('id')
                  ->on('business_locations')
                  ->onDelete('cascade');

            // معلومات المزود
            $table->string('provider_type')->default('mps');
            $table->string('provider_name')->default('MPS (ApexECR)');

            // حقل التفعيل
            $table->boolean('is_enabled')->default(false);

            // معلومات الاتصال
            $table->string('service_url', 500)->nullable();
            $table->string('terminal_id', 50)->nullable();
            $table->string('merchant_id', 50)->nullable();
            $table->string('merchant_name', 100)->nullable();
            $table->string('secure_key', 100)->nullable();
            $table->string('currency_code', 10)->default('400');

            // إعدادات الطباعة
            $table->boolean('print_receipt')->default(true);
            $table->integer('print_width')->default(40);
            $table->boolean('print_customer_copy')->default(true);
            $table->boolean('print_merchant_copy')->default(true);

            // إعدادات إضافية
            $table->boolean('enable_dcc')->default(false);
            $table->boolean('require_signature')->default(false);
            $table->integer('timeout_seconds')->default(60);

            // معلومات السجل
            $table->timestamp('last_test_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint مع اسم محدد
            $table->unique(['business_location_id', 'provider_type'], 'ecr_unique_location_provider');
            
            // إضافة الفهرس لحقل business_location_id
            $table->index(['business_location_id'], 'ecr_location_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ecr_integration_settings');
    }
}