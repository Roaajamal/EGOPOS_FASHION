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
        Schema::table('transactions', function (Blueprint $table) {
         DB::statement("ALTER TABLE `transactions` CHANGE `type` `type` ENUM('purchase','sell','expense','stock_adjustment','sell_transfer','purchase_transfer','opening_stock','sell_return','add_quantity') DEFAULT NULL");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
           DB::statement("ALTER TABLE `transactions` CHANGE `type` `type` ENUM('purchase','sell','expense','stock_adjustment','sell_transfer','purchase_transfer','opening_stock','sell_return','add_quantity') DEFAULT NULL");

        });
    }
};
