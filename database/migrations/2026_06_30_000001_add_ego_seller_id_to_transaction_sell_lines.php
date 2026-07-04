<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 🆕 عمود البائع المسؤول لكل سطر منتج (لاحتساب العمولة لكل بائع حسب منتجاته)
class AddEgoSellerIdToTransactionSellLines extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('transaction_sell_lines', 'ego_seller_id')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->unsignedInteger('ego_seller_id')->nullable();
                $table->index('ego_seller_id', 'tsl_ego_seller_idx');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('transaction_sell_lines', 'ego_seller_id')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->dropIndex('tsl_ego_seller_idx');
                $table->dropColumn('ego_seller_id');
            });
        }
    }
}
