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
            // Fields for Credit Invoice (فاتورة مرتجعات)
            if (!Schema::hasColumn('fatora_invoices', 'original_transaction_id')) {
                $table->integer('original_transaction_id')->unsigned()->nullable()->after('transaction_id')
                    ->comment('معرف الفاتورة الأصلية (للمرتجعات)');
            }
            if (!Schema::hasColumn('fatora_invoices', 'original_invoice_uuid')) {
                $table->string('original_invoice_uuid', 100)->nullable()->after('original_transaction_id')
                    ->comment('UUID الفاتورة الأصلية من JoFotara');
            }
            if (!Schema::hasColumn('fatora_invoices', 'original_invoice_amount')) {
                $table->decimal('original_invoice_amount', 22, 4)->nullable()->after('original_invoice_uuid')
                    ->comment('مبلغ الفاتورة الأصلية الكامل');
            }
            if (!Schema::hasColumn('fatora_invoices', 'return_reason')) {
                $table->text('return_reason')->nullable()->after('original_invoice_amount')
                    ->comment('سبب المرتجعات');
            }
            if (!Schema::hasColumn('fatora_invoices', 'is_credit_invoice')) {
                $table->boolean('is_credit_invoice')->default(false)->after('payment_method')
                    ->comment('هل هي فاتورة مرتجعات؟');
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
                'original_transaction_id',
                'original_invoice_uuid',
                'original_invoice_amount',
                'return_reason',
                'is_credit_invoice'
            ]);
        });
    }
};
