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
        Schema::table('fatora_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('fatora_invoices', 'invoice_type')) {
                $table->string('invoice_type', 50)->default('general_sales')->after('invoice_uuid');
            }
            if (!Schema::hasColumn('fatora_invoices', 'payment_method')) {
                $table->string('payment_method', 20)->default('cash')->after('invoice_type');
            }
            if (!Schema::hasColumn('fatora_invoices', 'qr_code')) {
                $table->text('qr_code')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('fatora_invoices', 'xml_content')) {
                $table->longText('xml_content')->nullable()->after('qr_code');
            }
            if (!Schema::hasColumn('fatora_invoices', 'response_data')) {
                $table->json('response_data')->nullable()->after('xml_content');
            }
            if (!Schema::hasColumn('fatora_invoices', 'status')) {
                $table->string('status', 50)->default('pending')->after('response_data');
            }
            if (!Schema::hasColumn('fatora_invoices', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
            if (!Schema::hasColumn('fatora_invoices', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('error_message');
            }
            if (!Schema::hasColumn('fatora_invoices', 'system_invoice_number')) {
                $table->string('system_invoice_number', 50)->nullable()->after('invoice_uuid')->comment('EINV_NUM from JoFotara');
            }
            if (!Schema::hasColumn('fatora_invoices', 'system_invoice_uuid')) {
                $table->string('system_invoice_uuid', 100)->nullable()->after('system_invoice_number')->comment('EINV_INV_UUID from JoFotara');
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
        Schema::table('fatora_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_type', 
                'payment_method',
                'system_invoice_number',
                'system_invoice_uuid',
                'qr_code', 
                'xml_content', 
                'response_data',
                'status',
                'error_message',
                'sent_at'
            ]);
        });
    }
};
