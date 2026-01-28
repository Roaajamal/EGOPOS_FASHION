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
        if (!Schema::hasTable('fatora_invoices')) {
        Schema::create('fatora_invoices', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_id')->unsigned();
            $table->integer('business_id')->unsigned();
            $table->string('invoice_uuid', 100)->unique()->comment('UUID للفاتورة في نظام الفوترة');
            $table->string('invoice_type', 50)->default('general_sales')->comment('general_sales, special_sales, income, credit');
            $table->string('payment_method', 20)->default('cash')->comment('cash or receivable');
            $table->text('qr_code')->nullable()->comment('QR Code للفاتورة');
            $table->longText('xml_content')->nullable()->comment('محتوى XML للفاتورة');
            $table->json('response_data')->nullable()->comment('استجابة API');
            $table->string('status', 50)->default('pending')->comment('pending, sent, accepted, rejected');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['transaction_id', 'business_id']);
            $table->index('status');
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
        Schema::dropIfExists('fatora_invoices');
    }
};
