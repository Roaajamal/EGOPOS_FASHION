<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

    if (Schema::hasTable('accounting_accounts_transactions')) {
        Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
            // القيمة بالعملة المختارة (مثلاً بالدولار)
            $table->decimal('amount_in_currency', 22, 4)->default(0)->after('amount');
            // ربط العملة
            $table->unsignedInteger('currency_id')->nullable()->after('amount_in_currency');
            // سعر الصرف وقت العملية
            $table->decimal('exchange_rate', 18, 4)->default(1.0000)->after('currency_id');
        });
    }

    // 2. بدلاً من accounting_transactions (غير الموجود)، سنضيفه لجدول transactions الأساسي
    // لأن قيود اليومية غالباً تخزن هناك في نظام Ultimate POS
    if (Schema::hasTable('transactions')) {
        Schema::table('transactions', function (Blueprint $table) {
            // نتحقق أولاً لربما العمود موجود مسبقاً من النظام الأساسي
            if (!Schema::hasColumn('transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 4)->default(1.0000)->after('final_total');
            }
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
       Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate']);
        });

        Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
            $table->dropColumn(['amount_in_currency', 'currency_id', 'exchange_rate']);
        });
    }
};
