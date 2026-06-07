<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLocationIdToAccountingAccountsTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_accounts_transactions', function (Blueprint $blueprint) {
            
            $blueprint->integer('location_id')->unsigned()->nullable()->after('accounting_account_id');
            
            $blueprint->foreign('location_id')
                      ->references('id')->on('business_locations')
                      ->onDelete('cascade');
                      
            $blueprint->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_accounts_transactions', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['location_id']);
            $blueprint->dropColumn('location_id');
        });
    }
}
