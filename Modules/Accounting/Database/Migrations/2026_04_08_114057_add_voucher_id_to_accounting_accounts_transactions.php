<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVoucherIdToAccountingAccountsTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
            // إضافة حقل الربط مع السندات بعد حقل transaction_id
            $table->integer('accounting_voucher_id')->unsigned()->nullable()->after('transaction_id');
            
            // إضافة فهرس للسرعة في التقارير
            $table->index('accounting_voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
            $table->dropColumn('accounting_voucher_id');
        });
    }
}